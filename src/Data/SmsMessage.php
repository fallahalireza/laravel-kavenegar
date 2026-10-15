<?php

declare(strict_types=1);

namespace FallahAlireza\Kavenegar\Data;

use FallahAlireza\Kavenegar\Exceptions\InvalidMessageException;
use FallahAlireza\Kavenegar\Support\PhoneNumber;

/**
 * یک پیامک متنی معمولی. شیء نامتغیر (immutable) و اعتبارسنجی‌شده در لحظه‌ی ساخت است.
 */
final readonly class SmsMessage
{
    public string $text;

    /** @var list<string> */
    public array $receptors;

    /**
     * @param  array<int, scalar>|string  $receptors  یک یا چند شماره
     * @param  string|null  $sender  اگر null باشد از شماره‌ی پیش‌فرض تنظیمات استفاده می‌شود
     * @param  int|null  $sendAt  زمان ارسال به‌صورت Unix timestamp (ارسال زمان‌بندی‌شده)
     * @param  string|null  $type  نوع پیامک طبق مستندات کاوه‌نگار (مثلاً flash)
     * @param  string|null  $localId  شناسه‌ی محلی برای رهگیری سمت خودتان
     */
    public function __construct(
        string $text,
        array|string $receptors,
        public ?string $sender = null,
        public ?int $sendAt = null,
        public ?string $type = null,
        public ?string $localId = null,
    ) {
        if (trim($text) === '') {
            throw InvalidMessageException::emptyText();
        }

        $normalized = PhoneNumber::normalizeMany($receptors);

        if ($normalized === []) {
            throw InvalidMessageException::noReceptor();
        }

        $this->text = $text;
        $this->receptors = $normalized;
    }

    /**
     * @param  array<int, scalar>|string  $receptors
     */
    public static function make(string $text, array|string $receptors, ?string $sender = null): self
    {
        return new self($text, $receptors, $sender);
    }
}
