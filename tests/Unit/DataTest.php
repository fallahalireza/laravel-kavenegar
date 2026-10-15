<?php

use FallahAlireza\Kavenegar\Data\LookupMessage;
use FallahAlireza\Kavenegar\Data\SmsMessage;
use FallahAlireza\Kavenegar\Data\SmsResult;
use FallahAlireza\Kavenegar\Enums\SmsStatus;
use FallahAlireza\Kavenegar\Exceptions\InvalidMessageException;
use FallahAlireza\Kavenegar\Support\PhoneNumber;

it('normalizes persian digits and separators in phone numbers', function () {
    expect(PhoneNumber::normalize('۰۹۱۲-۳۴۵ ۶۷۸۹'))->toBe('09123456789')
        ->and(PhoneNumber::normalize('+98 (912) 345-6789'))->toBe('989123456789');
});

it('rejects non numeric receptors', function () {
    PhoneNumber::normalize('09abc');
})->throws(InvalidMessageException::class);

it('de-duplicates receptors of an sms message', function () {
    $message = new SmsMessage('سلام', ['09121234567', '۰۹۱۲۱۲۳۴۵۶۷', '09351234567']);

    expect($message->receptors)->toBe(['09121234567', '09351234567']);
});

it('rejects empty text and empty receptors', function (string $text, array|string $receptors) {
    new SmsMessage($text, $receptors);
})->with([
    'empty text' => ['  ', '09121234567'],
    'no receptors' => ['سلام', []],
])->throws(InvalidMessageException::class);

it('maps lookup tokens to api parameter names', function () {
    $message = new LookupMessage('09121234567', 'verify', ['a', 'b', 'c', 'd', 'e']);

    expect($message->tokenParameters())->toBe([
        'token' => 'a', 'token2' => 'b', 'token3' => 'c', 'token10' => 'd', 'token20' => 'e',
    ]);
});

it('accepts a single scalar token and casts it to string', function () {
    expect((new LookupMessage('09121234567', 'verify', 12345))->tokenParameters())
        ->toBe(['token' => '12345']);
});

it('rejects invalid lookup input', function (array|string $tokens, string $template) {
    new LookupMessage('09121234567', $template, $tokens);
})->with([
    'no tokens' => [[], 'verify'],
    'too many tokens' => [['1', '2', '3', '4', '5', '6'], 'verify'],
    'empty template' => [['1'], ' '],
])->throws(InvalidMessageException::class);

it('builds a result from an api entry', function () {
    $result = SmsResult::fromArray([
        'messageid' => 8792343, 'message' => 'سلام', 'status' => 1, 'statustext' => 'در صف ارسال',
        'sender' => '10004346', 'receptor' => '09121234567', 'date' => 1700000000, 'cost' => 120,
    ]);

    expect($result->messageId)->toBe(8792343)
        ->and($result->status)->toBe(SmsStatus::Queued)
        ->and($result->cost)->toBe(120)
        ->and($result->localId)->toBeNull()
        ->and($result->toArray()['status'])->toBe(1);
});

it('labels every known status in persian', function () {
    expect(SmsStatus::Queued->label())->toBe('در صف ارسال قرار دارد')
        ->and(SmsStatus::Scheduled->label())->toBe('زمان‌بندی شده')
        ->and(SmsStatus::SentToTelecom->label())->toBe('ارسال شده به مخابرات')
        ->and(SmsStatus::SentToTelecomSame->label())->toBe('ارسال شده به مخابرات')
        ->and(SmsStatus::Failed->label())->toBe('خطا در ارسال')
        ->and(SmsStatus::Delivered->label())->toBe('رسیده به گیرنده')
        ->and(SmsStatus::Undelivered->label())->toBe('نرسیده به گیرنده')
        ->and(SmsStatus::Cancelled->label())->toBe('لغو شده')
        ->and(SmsStatus::Blocked->label())->toBe('مسدود شده توسط گیرنده')
        ->and(SmsStatus::InvalidMessageId->label())->toBe('شناسه پیامک نامعتبر است');
});

it('maps api status codes onto the enum and falls back for unknown codes', function () {
    $known = SmsResult::fromArray(['messageid' => 1, 'status' => 10, 'statustext' => 'رسیده به گیرنده']);
    $unknown = SmsResult::fromArray(['messageid' => 2, 'status' => 999, 'statustext' => 'وضعیت جدید']);

    expect($known->status)->toBe(SmsStatus::Delivered)
        ->and($known->statusLabel())->toBe('رسیده به گیرنده')
        ->and($unknown->status)->toBeNull()
        ->and($unknown->statusLabel())->toBe('وضعیت جدید')
        ->and((new SmsResult)->statusLabel())->toBeNull();
});
