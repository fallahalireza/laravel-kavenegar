<?php

declare(strict_types=1);

namespace FallahAlireza\Kavenegar\Enums;

/**
 * وضعیت عددی پیامک در پاسخ کاوه‌نگار (جدول ۲ مستندات REST).
 *
 * فیلد `status` در پاسخ API یک عدد است؛ این enum آن را تایپ‌دار و برچسب‌دار می‌کند:
 *
 *     if ($result->status === SmsStatus::Delivered) { ... }
 *     echo $result->status?->label(); // «رسیده به گیرنده»
 */
enum SmsStatus: int
{
    /** در صف ارسال قرار دارد. */
    case Queued = 1;

    /** زمان‌بندی شده؛ ارسال در تاریخ معین. */
    case Scheduled = 2;

    /** ارسال شده به مخابرات. */
    case SentToTelecom = 4;

    /** طبق مستندات کاوه‌نگار همانند وضعیت ۴ است. */
    case SentToTelecomSame = 5;

    /** خطا در ارسال پیام (Failed)؛ پیامک به گیرنده نمی‌رسد. */
    case Failed = 6;

    /** رسیده به گیرنده (Delivered). */
    case Delivered = 10;

    /** نرسیده به گیرنده (Undelivered)؛ مثلاً خاموش یا خارج از دسترس بودن گیرنده. */
    case Undelivered = 11;

    /** ارسال لغو شده یا مشکلی پیش آمده که هزینه‌ی آن برگشت داده می‌شود. */
    case Cancelled = 13;

    /** گیرنده تمایلی به دریافت پیامک از خطوط تبلیغاتی نداشته و شماره بلاک شده است. */
    case Blocked = 14;

    /** شناسه‌ی پیامک نامعتبر است، آرشیو شده یا متعلق به این حساب نیست. */
    case InvalidMessageId = 100;

    /**
     * برچسب فارسی وضعیت، مطابق متن `statustext` مستندات کاوه‌نگار.
     */
    public function label(): string
    {
        return match ($this) {
            self::Queued => 'در صف ارسال قرار دارد',
            self::Scheduled => 'زمان‌بندی شده',
            self::SentToTelecom, self::SentToTelecomSame => 'ارسال شده به مخابرات',
            self::Failed => 'خطا در ارسال',
            self::Delivered => 'رسیده به گیرنده',
            self::Undelivered => 'نرسیده به گیرنده',
            self::Cancelled => 'لغو شده',
            self::Blocked => 'مسدود شده توسط گیرنده',
            self::InvalidMessageId => 'شناسه پیامک نامعتبر است',
        };
    }
}
