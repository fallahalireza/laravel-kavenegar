<?php

declare(strict_types=1);

namespace FallahAlireza\Kavenegar\Facades;

use FallahAlireza\Kavenegar\Contracts\SmsGateway;
use FallahAlireza\Kavenegar\SmsManager;
use FallahAlireza\Kavenegar\Testing\FakeGateway;
use Illuminate\Support\Facades\Facade;

/**
 * @method static list<\FallahAlireza\Kavenegar\Data\SmsResult> send(\FallahAlireza\Kavenegar\Data\SmsMessage $message)
 * @method static list<\FallahAlireza\Kavenegar\Data\SmsResult> lookup(\FallahAlireza\Kavenegar\Data\LookupMessage $message)
 * @method static list<\FallahAlireza\Kavenegar\Data\SmsResult> status(array<int, int|string>|int|string $messageIds)
 * @method static list<\FallahAlireza\Kavenegar\Data\SmsResult> cancel(array<int, int|string>|int|string $messageIds)
 * @method static SmsGateway driver(string|null $driver = null)
 * @method static SmsManager extend(string $driver, \Closure $callback)
 *
 * @see SmsManager
 */
class Sms extends Facade
{
    /**
     * همه‌ی ارسال‌ها را به FakeGateway هدایت می‌کند و آن را برای assert برمی‌گرداند.
     */
    public static function fake(): FakeGateway
    {
        return static::getFacadeRoot()->fake();
    }

    protected static function getFacadeAccessor(): string
    {
        return SmsManager::class;
    }
}
