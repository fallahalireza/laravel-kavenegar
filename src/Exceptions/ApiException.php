<?php

declare(strict_types=1);

namespace FallahAlireza\Kavenegar\Exceptions;

/**
 * وب‌سرویس پاسخ داده، اما کد وضعیت آن 200 نیست (مثلاً اعتبار ناکافی یا پارامتر نامعتبر).
 * کد خطای کاوه‌نگار در getCode() در دسترس است.
 */
class ApiException extends KavenegarException
{
    public static function fromStatus(int $status, string $message): self
    {
        return new self($message !== '' ? $message : "خطای کاوه‌نگار با کد {$status}", $status);
    }
}
