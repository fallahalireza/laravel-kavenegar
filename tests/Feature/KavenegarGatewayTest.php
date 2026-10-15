<?php

use FallahAlireza\Kavenegar\Data\LookupMessage;
use FallahAlireza\Kavenegar\Data\SmsMessage;
use FallahAlireza\Kavenegar\Enums\SmsStatus;
use FallahAlireza\Kavenegar\Exceptions\ApiException;
use FallahAlireza\Kavenegar\Exceptions\ConfigurationException;
use FallahAlireza\Kavenegar\Exceptions\TransportException;
use FallahAlireza\Kavenegar\Gateways\KavenegarGateway;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

function gateway(array $options = [], ?string $sender = '10004346'): KavenegarGateway
{
    return new KavenegarGateway(app(Factory::class), 'SECRET-KEY', $sender, $options);
}

function okResponse(array $entries): array
{
    return ['return' => ['status' => 200, 'message' => 'تایید شد'], 'entries' => $entries];
}

beforeEach(fn () => Http::preventStrayRequests());

it('posts a send request with the expected url and form fields', function () {
    Http::fake(['api.kavenegar.com/*' => Http::response(okResponse([
        ['messageid' => 1, 'receptor' => '09121234567', 'status' => 1, 'cost' => 100],
        ['messageid' => 2, 'receptor' => '09351234567', 'status' => 1, 'cost' => 100],
    ]))]);

    $results = gateway()->send(new SmsMessage('سلام', ['09121234567', '09351234567']));

    Http::assertSent(fn (Request $request) => $request->url() === 'https://api.kavenegar.com/v1/SECRET-KEY/sms/send.json'
        && $request->method() === 'POST'
        && $request['receptor'] === '09121234567,09351234567'
        && $request['sender'] === '10004346'
        && $request['message'] === 'سلام'
        && ! isset($request['date'])
        && ! isset($request['localid']));

    expect($results)->toHaveCount(2)
        ->and($results[0]->messageId)->toBe(1)
        ->and($results[0]->status)->toBe(SmsStatus::Queued)
        ->and($results[1]->receptor)->toBe('09351234567');
});

it('prefers the sender on the message over the default sender', function () {
    Http::fake(['*' => Http::response(okResponse([]))]);

    gateway()->send(new SmsMessage('سلام', '09121234567', sender: '2000500'));

    Http::assertSent(fn (Request $request) => $request['sender'] === '2000500');
});

it('fails before any request when no sender is available', function () {
    Http::fake();

    gateway(sender: null)->send(new SmsMessage('سلام', '09121234567'));
})->throws(ConfigurationException::class);

it('requires an api key', function () {
    new KavenegarGateway(app(Factory::class), '  ');
})->throws(ConfigurationException::class);

it('sends a verify lookup with positional tokens', function () {
    Http::fake(['*' => Http::response(okResponse(['messageid' => 7, 'receptor' => '09121234567']))]);

    $results = gateway()->lookup(new LookupMessage('09121234567', 'verify', ['1234', 'x']));

    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/SECRET-KEY/verify/lookup.json')
        && $request['template'] === 'verify'
        && $request['token'] === '1234'
        && $request['token2'] === 'x'
        && ! isset($request['token3']));

    // entries تکی (غیر آرایه) باید به لیست تبدیل شود
    expect($results)->toHaveCount(1)->and($results[0]->messageId)->toBe(7);
});

it('throws ApiException carrying the kavenegar status code', function () {
    Http::fake(['*' => Http::response(['return' => ['status' => 418, 'message' => 'اعتبار کافی نیست']], 400)]);

    try {
        gateway()->send(new SmsMessage('سلام', '09121234567'));
        $this->fail('ApiException expected');
    } catch (ApiException $e) {
        expect($e->getCode())->toBe(418)->and($e->getMessage())->toBe('اعتبار کافی نیست');
    }
});

it('throws TransportException for a non json response', function () {
    Http::fake(['*' => Http::response('<html>Bad Gateway</html>', 502)]);

    gateway()->send(new SmsMessage('سلام', '09121234567'));
})->throws(TransportException::class);

it('masks the api key in connection errors', function () {
    Http::fake(fn () => throw new ConnectionException('cURL error 28 for https://api.kavenegar.com/v1/SECRET-KEY/sms/send.json'));

    try {
        gateway()->send(new SmsMessage('سلام', '09121234567'));
        $this->fail('TransportException expected');
    } catch (TransportException $e) {
        expect($e->getMessage())->not->toContain('SECRET-KEY')->toContain('***');
    }
});

it('retries only connection failures when retries are enabled', function () {
    $attempts = 0;
    Http::fake(function () use (&$attempts) {
        $attempts++;

        return $attempts < 3
            ? throw new ConnectionException('timeout')
            : Http::response(okResponse([['messageid' => 1]]));
    });

    $results = gateway(['retries' => 3, 'retry_delay' => 0])->send(new SmsMessage('سلام', '09121234567'));

    expect($attempts)->toBe(3)->and($results)->toHaveCount(1);
});

it('never retries an api level error to avoid duplicate sms', function () {
    $attempts = 0;
    Http::fake(function () use (&$attempts) {
        $attempts++;

        return Http::response(['return' => ['status' => 500, 'message' => 'خطا']], 500);
    });

    try {
        gateway(['retries' => 3, 'retry_delay' => 0])->send(new SmsMessage('سلام', '09121234567'));
    } catch (ApiException) {
    }

    expect($attempts)->toBe(1);
});

it('sends status and cancel requests with joined message ids', function () {
    Http::fake(['*' => Http::response(okResponse([['messageid' => 1, 'status' => 10]]))]);

    gateway()->status([1, 2]);
    gateway()->cancel('5');

    Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/sms/status.json') && $r['messageid'] === '1,2');
    Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/sms/cancel.json') && $r['messageid'] === '5');
});
