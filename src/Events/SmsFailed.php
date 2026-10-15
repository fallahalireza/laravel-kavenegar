<?php

declare(strict_types=1);

namespace FallahAlireza\Kavenegar\Events;

use FallahAlireza\Kavenegar\Data\LookupMessage;
use FallahAlireza\Kavenegar\Data\SmsMessage;
use FallahAlireza\Kavenegar\Exceptions\KavenegarException;

/**
 * هنگام شکست ارسال؛ استثنا بعد از انتشار این رویداد دوباره پرتاب می‌شود.
 */
final readonly class SmsFailed
{
    public function __construct(
        public SmsMessage|LookupMessage $message,
        public KavenegarException $exception,
    ) {}
}
