<?php

declare(strict_types=1);

namespace FallahAlireza\Kavenegar\Notifications;

use FallahAlireza\Kavenegar\Data\LookupMessage;
use FallahAlireza\Kavenegar\Data\SmsMessage;

/**
 * پیام قابل‌خواندن برای متد toKavenegar() در Notification.
 *
 *   return (new KavenegarMessage('سفارش شما ثبت شد'))->from('10004346');
 *   return (new KavenegarMessage)->verifyLookup('verify', ['12345']);
 */
class KavenegarMessage
{
    private ?string $from = null;

    private ?string $to = null;

    private ?string $driver = null;

    private ?int $sendAt = null;

    private ?string $type = null;

    private ?string $localId = null;

    private ?string $template = null;

    /** @var list<string> */
    private array $tokens = [];

    public function __construct(private string $content = '') {}

    public function content(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function from(string $from): static
    {
        $this->from = $from;

        return $this;
    }

    public function to(string $to): static
    {
        $this->to = $to;

        return $this;
    }

    /**
     * ارسال از طریق درایوری غیر از پیش‌فرض.
     */
    public function usingDriver(string $driver): static
    {
        $this->driver = $driver;

        return $this;
    }

    public function sendAt(int $timestamp): static
    {
        $this->sendAt = $timestamp;

        return $this;
    }

    public function type(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function localId(string $localId): static
    {
        $this->localId = $localId;

        return $this;
    }

    /**
     * @param  array<int, string|int|float>|string|int|float  $tokens
     */
    public function verifyLookup(string $template, array|string|int|float $tokens): static
    {
        $this->template = $template;
        $this->tokens = array_values(array_map(static fn ($t): string => (string) $t, (array) $tokens));

        return $this;
    }

    public function receptor(): ?string
    {
        return $this->to;
    }

    public function driver(): ?string
    {
        return $this->driver;
    }

    public function isLookup(): bool
    {
        return $this->template !== null;
    }

    public function toSms(string $receptor): SmsMessage
    {
        return new SmsMessage($this->content, $receptor, $this->from, $this->sendAt, $this->type, $this->localId);
    }

    public function toLookup(string $receptor): LookupMessage
    {
        return new LookupMessage($receptor, (string) $this->template, $this->tokens, $this->type);
    }
}
