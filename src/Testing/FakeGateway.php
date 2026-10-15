<?php

declare(strict_types=1);

namespace FallahAlireza\Kavenegar\Testing;

use FallahAlireza\Kavenegar\Contracts\SmsGateway;
use FallahAlireza\Kavenegar\Data\LookupMessage;
use FallahAlireza\Kavenegar\Data\SmsMessage;
use FallahAlireza\Kavenegar\Data\SmsResult;
use FallahAlireza\Kavenegar\Enums\SmsStatus;
use FallahAlireza\Kavenegar\Support\PhoneNumber;
use PHPUnit\Framework\Assert as PHPUnit;
use Throwable;

/**
 * درایور تقلبی برای تست: هیچ درخواستی نمی‌فرستد و پیام‌ها را برای assert ذخیره می‌کند.
 * استفاده: $fake = Sms::fake(); ... $fake->assertSentTo('09121234567');
 */
final class FakeGateway implements SmsGateway
{
    /** @var list<SmsMessage> */
    private array $sent = [];

    /** @var list<LookupMessage> */
    private array $lookups = [];

    private ?Throwable $failure = null;

    private int $nextId = 1000;

    /**
     * شبیه‌سازی خطا: تا وقتی null نشود، همه‌ی ارسال‌ها این استثنا را پرتاب می‌کنند.
     */
    public function failWith(?Throwable $exception): self
    {
        $this->failure = $exception;

        return $this;
    }

    public function send(SmsMessage $message): array
    {
        $this->throwIfFailing();
        $this->sent[] = $message;

        return array_map(
            fn (string $receptor): SmsResult => new SmsResult(
                messageId: $this->nextId++,
                message: $message->text,
                status: SmsStatus::Queued,
                statusText: 'fake',
                sender: $message->sender,
                receptor: $receptor,
                date: time(),
                cost: 0,
            ),
            $message->receptors,
        );
    }

    public function lookup(LookupMessage $message): array
    {
        $this->throwIfFailing();
        $this->lookups[] = $message;

        return [new SmsResult(messageId: $this->nextId++, status: SmsStatus::Queued, statusText: 'fake', receptor: $message->receptor)];
    }

    public function status(array|int|string $messageIds): array
    {
        return [];
    }

    public function cancel(array|int|string $messageIds): array
    {
        return [];
    }

    /**
     * @param  (callable(SmsMessage): bool)|null  $callback
     * @return list<SmsMessage>
     */
    public function sent(?callable $callback = null): array
    {
        return array_values(array_filter(
            $this->sent,
            static fn (SmsMessage $m): bool => $callback === null || $callback($m),
        ));
    }

    /**
     * @param  (callable(LookupMessage): bool)|null  $callback
     * @return list<LookupMessage>
     */
    public function lookups(?callable $callback = null): array
    {
        return array_values(array_filter(
            $this->lookups,
            static fn (LookupMessage $m): bool => $callback === null || $callback($m),
        ));
    }

    /**
     * @param  (callable(SmsMessage): bool)|null  $callback
     */
    public function assertSent(?callable $callback = null, ?int $times = null): void
    {
        $count = count($this->sent($callback));

        if ($times === null) {
            PHPUnit::assertGreaterThan(0, $count, 'هیچ پیامکی با شرط داده‌شده ارسال نشده است.');

            return;
        }

        PHPUnit::assertSame($times, $count, "انتظار {$times} پیامک می‌رفت اما {$count} پیامک ارسال شده است.");
    }

    /**
     * @param  (callable(SmsMessage): bool)|null  $callback
     */
    public function assertSentTo(string $receptor, ?callable $callback = null): void
    {
        $receptor = PhoneNumber::normalize($receptor);

        $this->assertSent(
            static fn (SmsMessage $m): bool => in_array($receptor, $m->receptors, true)
                && ($callback === null || $callback($m)),
        );
    }

    /**
     * @param  (callable(LookupMessage): bool)|null  $callback
     */
    public function assertLookupSent(?callable $callback = null, ?int $times = null): void
    {
        $count = count($this->lookups($callback));

        if ($times === null) {
            PHPUnit::assertGreaterThan(0, $count, 'هیچ پیام قالب‌محوری (Lookup) با شرط داده‌شده ارسال نشده است.');

            return;
        }

        PHPUnit::assertSame($times, $count, "انتظار {$times} پیام Lookup می‌رفت اما {$count} پیام ارسال شده است.");
    }

    public function assertNothingSent(): void
    {
        PHPUnit::assertCount(0, $this->sent, 'انتظار می‌رفت هیچ پیامکی ارسال نشود.');
        PHPUnit::assertCount(0, $this->lookups, 'انتظار می‌رفت هیچ پیام Lookup ارسال نشود.');
    }

    private function throwIfFailing(): void
    {
        if ($this->failure !== null) {
            throw $this->failure;
        }
    }
}
