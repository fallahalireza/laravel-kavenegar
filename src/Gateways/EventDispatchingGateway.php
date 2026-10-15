<?php

declare(strict_types=1);

namespace FallahAlireza\Kavenegar\Gateways;

use FallahAlireza\Kavenegar\Contracts\SmsGateway;
use FallahAlireza\Kavenegar\Data\LookupMessage;
use FallahAlireza\Kavenegar\Data\SmsMessage;
use FallahAlireza\Kavenegar\Data\SmsResult;
use FallahAlireza\Kavenegar\Events\SmsFailed;
use FallahAlireza\Kavenegar\Events\SmsSent;
use FallahAlireza\Kavenegar\Exceptions\KavenegarException;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Decorator: هر درایوری را می‌پوشاند و رویدادهای SmsSent/SmsFailed را منتشر می‌کند،
 * بدون این‌که خود درایورها از رویداد چیزی بدانند.
 */
final class EventDispatchingGateway implements SmsGateway
{
    public function __construct(
        private readonly SmsGateway $inner,
        private readonly Dispatcher $events,
    ) {}

    public function send(SmsMessage $message): array
    {
        return $this->dispatching($message, fn (): array => $this->inner->send($message));
    }

    public function lookup(LookupMessage $message): array
    {
        return $this->dispatching($message, fn (): array => $this->inner->lookup($message));
    }

    public function status(array|int|string $messageIds): array
    {
        return $this->inner->status($messageIds);
    }

    public function cancel(array|int|string $messageIds): array
    {
        return $this->inner->cancel($messageIds);
    }

    /**
     * @param  callable(): list<SmsResult>  $operation
     * @return list<SmsResult>
     */
    private function dispatching(SmsMessage|LookupMessage $message, callable $operation): array
    {
        try {
            $results = $operation();
        } catch (KavenegarException $e) {
            $this->events->dispatch(new SmsFailed($message, $e));

            throw $e;
        }

        $this->events->dispatch(new SmsSent($message, $results));

        return $results;
    }
}
