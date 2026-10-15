<?php

use FallahAlireza\Kavenegar\Data\LookupMessage;
use FallahAlireza\Kavenegar\Data\SmsMessage;
use FallahAlireza\Kavenegar\Facades\Sms;
use PHPUnit\Framework\AssertionFailedError;

it('records sent messages and exposes assertions', function () {
    $fake = Sms::fake();

    Sms::send(new SmsMessage('سلام', ['09121234567', '09351234567']));
    Sms::lookup(new LookupMessage('09121234567', 'verify', '1234'));

    $fake->assertSent(times: 1);
    $fake->assertSentTo('09351234567');
    $fake->assertLookupSent(times: 1);
    expect($fake->sent())->toHaveCount(1);
});

it('fails assertions when nothing matches', function () {
    $fake = Sms::fake();

    Sms::send(new SmsMessage('سلام', '09121234567'));

    $fake->assertSentTo('09999999999');
})->throws(AssertionFailedError::class);

it('asserts nothing was sent', function () {
    Sms::fake()->assertNothingSent();
});
