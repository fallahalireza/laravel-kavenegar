<?php

declare(strict_types=1);

namespace FallahAlireza\Kavenegar;

use FallahAlireza\Kavenegar\Contracts\SmsGateway;
use FallahAlireza\Kavenegar\Data\LookupMessage;
use FallahAlireza\Kavenegar\Data\SmsMessage;
use FallahAlireza\Kavenegar\Gateways\EventDispatchingGateway;
use FallahAlireza\Kavenegar\Gateways\KavenegarGateway;
use FallahAlireza\Kavenegar\Gateways\LogGateway;
use FallahAlireza\Kavenegar\Testing\FakeGateway;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Manager;

/**
 * مدیر درایورها (الگوی Manager لاراول، مثل Mail و Cache).
 *
 * - انتخاب درایور: Sms::driver('log')->send(...)
 * - افزودن درایور سفارشی: Sms::extend('my-provider', fn ($app) => new MyGateway(...));
 * - خودش هم SmsGateway است و فراخوانی‌ها را به درایور پیش‌فرض می‌فرستد.
 */
class SmsManager extends Manager implements SmsGateway
{
    private ?FakeGateway $fake = null;

    public function getDefaultDriver(): string
    {
        return (string) $this->config->get('kavenegar.default', 'kavenegar');
    }

    /**
     * جایگزین کردن همه‌ی درایورها با FakeGateway (برای تست).
     */
    public function fake(): FakeGateway
    {
        return $this->fake = new FakeGateway;
    }

    public function driver($driver = null)
    {
        return $this->fake ?? parent::driver($driver);
    }

    public function send(SmsMessage $message): array
    {
        return $this->driver()->send($message);
    }

    public function lookup(LookupMessage $message): array
    {
        return $this->driver()->lookup($message);
    }

    public function status(array|int|string $messageIds): array
    {
        return $this->driver()->status($messageIds);
    }

    public function cancel(array|int|string $messageIds): array
    {
        return $this->driver()->cancel($messageIds);
    }

    protected function createDriver($driver)
    {
        $gateway = parent::createDriver($driver);

        if ($gateway instanceof SmsGateway && $this->config->get('kavenegar.events', true)) {
            return new EventDispatchingGateway($gateway, $this->container->make(Dispatcher::class));
        }

        return $gateway;
    }

    protected function createKavenegarDriver(): SmsGateway
    {
        /** @var array<string, mixed> $config */
        $config = $this->config->get('kavenegar.drivers.kavenegar', []);

        return new KavenegarGateway(
            $this->container->make(HttpFactory::class),
            (string) ($config['api_key'] ?? ''),
            isset($config['sender']) ? (string) $config['sender'] : null,
            $config,
        );
    }

    protected function createLogDriver(): SmsGateway
    {
        $channel = $this->config->get('kavenegar.drivers.log.channel');

        return new LogGateway($this->container->make('log')->channel($channel));
    }
}
