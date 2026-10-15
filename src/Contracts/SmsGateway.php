<?php

declare(strict_types=1);

namespace FallahAlireza\Kavenegar\Contracts;

use FallahAlireza\Kavenegar\Data\LookupMessage;
use FallahAlireza\Kavenegar\Data\SmsMessage;
use FallahAlireza\Kavenegar\Data\SmsResult;
use FallahAlireza\Kavenegar\Exceptions\KavenegarException;

/**
 * قرارداد هر درایور ارسال پیامک. کدهای برنامه فقط به همین اینترفیس وابسته می‌شوند،
 * پس تعویض درایور (کاوه‌نگار، لاگ، Fake یا سرویس دیگر) بدون تغییر در کد ممکن است.
 */
interface SmsGateway
{
    /**
     * @return list<SmsResult>
     *
     * @throws KavenegarException
     */
    public function send(SmsMessage $message): array;

    /**
     * @return list<SmsResult>
     *
     * @throws KavenegarException
     */
    public function lookup(LookupMessage $message): array;

    /**
     * @param  array<int, int|string>|int|string  $messageIds
     * @return list<SmsResult>
     *
     * @throws KavenegarException
     */
    public function status(array|int|string $messageIds): array;

    /**
     * @param  array<int, int|string>|int|string  $messageIds
     * @return list<SmsResult>
     *
     * @throws KavenegarException
     */
    public function cancel(array|int|string $messageIds): array;
}
