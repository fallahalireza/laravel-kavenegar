<?php

declare(strict_types=1);

namespace FallahAlireza\Kavenegar\Events;

use FallahAlireza\Kavenegar\Data\LookupMessage;
use FallahAlireza\Kavenegar\Data\SmsMessage;
use FallahAlireza\Kavenegar\Data\SmsResult;

/**
 * پس از پذیرش موفق پیامک توسط درایور. برای لاگ، آمار یا ذخیره‌ی messageid مناسب است.
 */
final readonly class SmsSent
{
    /**
     * @param  list<SmsResult>  $results
     */
    public function __construct(
        public SmsMessage|LookupMessage $message,
        public array $results,
    ) {}
}
