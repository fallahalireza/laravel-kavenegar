<?php

declare(strict_types=1);

namespace FallahAlireza\Kavenegar\Exceptions;

/**
 * تنظیمات ناقص است (مثلاً کلید API یا شماره‌ی ارسال‌کننده تعریف نشده).
 */
class ConfigurationException extends KavenegarException
{
    public static function missingApiKey(): self
    {
        return new self('کلید API کاوه‌نگار تنظیم نشده است (KAVENEGAR_API_KEY).');
    }

    public static function missingSender(): self
    {
        return new self('شماره‌ی ارسال‌کننده تعیین نشده است؛ آن را روی پیام یا در KAVENEGAR_SENDER تنظیم کنید.');
    }
}
