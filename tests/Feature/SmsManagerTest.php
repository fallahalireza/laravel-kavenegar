<?php

use FallahAlireza\Kavenegar\Contracts\SmsGateway;
use FallahAlireza\Kavenegar\Data\LookupMessage;
use FallahAlireza\Kavenegar\Data\SmsMessage;
use FallahAlireza\Kavenegar\Data\SmsResult;
use FallahAlireza\Kavenegar\Events\SmsFailed;
use FallahAlireza\Kavenegar\Events\SmsSent;
use FallahAlireza\Kavenegar\Exceptions\ApiException;
use FallahAlireza\Kavenegar\Facades\Sms;
use FallahAlireza\Kavenegar\Gateways\EventDispatchingGateway;
use FallahAlireza\Kavenegar\Gateways\KavenegarGateway;
use FallahAlireza\Kavenegar\SmsManager;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

it('resolves the manager through the SmsGateway contract', function () {
    expect(app(SmsGateway::class))->toBe(app(SmsManager::class));
});

it('wraps drivers with event dispatching by default', function () {
    expect(Sms::driver('kavenegar'))->toBeInstanceOf(EventDispatchingGateway::class);
});

it('can disable events through config', function () {
    config()->set('kavenegar.events', false);

    expect(app(SmsManager::class)->driver('kavenegar'))->toBeInstanceOf(KavenegarGateway::class);
});

it('writes to the log with the log driver without any http call', function () {
    Http::preventStrayRequests();
    config()->set('kavenegar.default', 'log');

    $results = Sms::send(new SmsMessage('سلام', '09121234567'));

    expect($results)->toHaveCount(1)->and($results[0]->statusText)->toBe('logged');
});

it('supports custom drivers through extend()', function () {
    $custom = new class implements SmsGateway
    {
        public array $received = [];

        public function send(SmsMessage $message): array
        {
            $this->received[] = $message;

            return [new SmsResult(statusText: 'custom')];
        }

        public function lookup(LookupMessage $message): array
        {
            return [];
        }

        public function status(array|int|string $messageIds): array
        {
            return [];
        }

        public function cancel(array|int|string $messageIds): array
        {
            return [];
        }
    };

    Sms::extend('custom', fn () => $custom);
    config()->set('kavenegar.default', 'custom');

    $result = Sms::send(new SmsMessage('سلام', '09121234567'));

    expect($result[0]->statusText)->toBe('custom')->and($custom->received)->toHaveCount(1);
});

it('dispatches SmsSent after a successful send', function () {
    Event::fake([SmsSent::class, SmsFailed::class]);
    Http::fake(['*' => Http::response(['return' => ['status' => 200], 'entries' => [['messageid' => 1]]])]);

    Sms::send(new SmsMessage('سلام', '09121234567'));

    Event::assertDispatched(SmsSent::class, fn (SmsSent $e) => $e->results[0]->messageId === 1);
    Event::assertNotDispatched(SmsFailed::class);
});

it('dispatches SmsFailed and rethrows when the api rejects the message', function () {
    Event::fake([SmsSent::class, SmsFailed::class]);
    Http::fake(['*' => Http::response(['return' => ['status' => 418, 'message' => 'اعتبار کافی نیست']])]);

    try {
        Sms::send(new SmsMessage('سلام', '09121234567'));
        $this->fail('ApiException expected');
    } catch (ApiException) {
        Event::assertDispatched(SmsFailed::class, fn (SmsFailed $e) => $e->exception->getCode() === 418);
        Event::assertNotDispatched(SmsSent::class);
    }
});
