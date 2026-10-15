<?php

use FallahAlireza\Kavenegar\Data\LookupMessage;
use FallahAlireza\Kavenegar\Data\SmsMessage;
use FallahAlireza\Kavenegar\Exceptions\ApiException;
use FallahAlireza\Kavenegar\Exceptions\InvalidMessageException;
use FallahAlireza\Kavenegar\Facades\Sms;
use FallahAlireza\Kavenegar\Notifications\KavenegarMessage;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class TestUser
{
    use Notifiable;

    public function __construct(public ?string $mobile) {}

    public function routeNotificationForKavenegar(): ?string
    {
        return $this->mobile;
    }
}

class InvoicePaid extends Notification
{
    public function __construct(private ?KavenegarMessage $message = null) {}

    public function via($notifiable): array
    {
        return ['kavenegar'];
    }

    public function toKavenegar($notifiable): KavenegarMessage
    {
        return $this->message ?? (new KavenegarMessage('فاکتور شما پرداخت شد'))->from('10004346');
    }
}

it('sends a text notification to the notifiable mobile', function () {
    $fake = Sms::fake();

    (new TestUser('۰۹۱۲۱۲۳۴۵۶۷'))->notify(new InvoicePaid);

    $fake->assertSentTo('09121234567', fn (SmsMessage $m) => $m->text === 'فاکتور شما پرداخت شد' && $m->sender === '10004346');
});

it('sends a verify lookup notification', function () {
    $fake = Sms::fake();

    (new TestUser('09121234567'))->notify(new InvoicePaid((new KavenegarMessage)->verifyLookup('verify', ['1234'])));

    $fake->assertLookupSent(fn (LookupMessage $m) => $m->template === 'verify' && $m->tokens === ['1234'], times: 1);
    $fake->assertSent(times: 0);
});

it('prefers the receptor set on the message', function () {
    $fake = Sms::fake();

    (new TestUser('09121234567'))->notify(new InvoicePaid((new KavenegarMessage('سلام'))->to('09351234567')));

    $fake->assertSentTo('09351234567');
});

it('supports on-demand notifications', function () {
    $fake = Sms::fake();

    NotificationFacade::route('kavenegar', '09121234567')->notify(new InvoicePaid);

    $fake->assertSentTo('09121234567');
});

it('silently skips when the notifiable has no mobile', function () {
    $fake = Sms::fake();

    (new TestUser(null))->notify(new InvoicePaid);

    $fake->assertNothingSent();
});

it('lets gateway errors bubble up so queued notifications can retry', function () {
    Sms::fake()->failWith(ApiException::fromStatus(418, 'اعتبار کافی نیست'));

    (new TestUser('09121234567'))->notify(new InvoicePaid);
})->throws(ApiException::class);

it('throws a clear error when the notification has no toKavenegar method', function () {
    $notification = new class extends Notification
    {
        public function via($notifiable): array
        {
            return ['kavenegar'];
        }
    };

    (new TestUser('09121234567'))->notify($notification);
})->throws(InvalidMessageException::class);
