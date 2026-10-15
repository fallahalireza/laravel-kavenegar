<?php

declare(strict_types=1);

namespace FallahAlireza\Kavenegar\Exceptions;

/**
 * مشکل در ارتباط با وب‌سرویس (قطعی شبکه، timeout، پاسخ غیر JSON).
 */
class TransportException extends KavenegarException
{
    public static function invalidResponse(int $httpStatus): self
    {
        return new self("پاسخ نامعتبر از کاوه‌نگار دریافت شد (HTTP {$httpStatus}).", $httpStatus);
    }
}
