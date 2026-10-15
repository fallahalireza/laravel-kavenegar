<?php

declare(strict_types=1);

namespace FallahAlireza\Kavenegar\Exceptions;

/**
 * پیام قبل از ارسال، اعتبارسنجی نشده است (متن خالی، گیرنده‌ی نامعتبر و ...).
 * این خطا بدون تماس با شبکه رخ می‌دهد.
 */
class InvalidMessageException extends KavenegarException
{
    public static function emptyText(): self
    {
        return new self('متن پیامک نمی‌تواند خالی باشد.');
    }

    public static function noReceptor(): self
    {
        return new self('حداقل یک گیرنده لازم است.');
    }

    public static function invalidReceptor(string $value): self
    {
        return new self("شماره‌ی گیرنده نامعتبر است: «{$value}»");
    }

    public static function emptyTemplate(): self
    {
        return new self('نام قالب (template) برای ارسال تأیید (VerifyLookup) الزامی است.');
    }

    public static function invalidTokens(int $count): self
    {
        return new self("تعداد توکن‌ها باید بین ۱ تا ۵ باشد؛ {$count} توکن داده شد.");
    }

    public static function missingKavenegarMessage(string $notification): self
    {
        return new self("نوتیفیکیشن {$notification} باید متد toKavenegar() داشته باشد و یک KavenegarMessage برگرداند.");
    }
}
