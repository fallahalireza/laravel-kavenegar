<?php

declare(strict_types=1);

namespace FallahAlireza\Kavenegar\Notifications;

use FallahAlireza\Kavenegar\Data\SmsResult;
use FallahAlireza\Kavenegar\Exceptions\InvalidMessageException;
use FallahAlireza\Kavenegar\Exceptions\KavenegarException;
use FallahAlireza\Kavenegar\SmsManager;
use Illuminate\Notifications\Notification;

/**
 * کانال Notification با نام «kavenegar».
 * Manager تزریق می‌شود (نه Facade)، پس در تست راحت با Fake جایگزین می‌شود.
 */
class KavenegarChannel
{
    public function __construct(private readonly SmsManager $sms) {}

    /**
     * @param  object  $notifiable  مدل دارای routeNotificationForKavenegar() یا AnonymousNotifiable
     * @return list<SmsResult>|null null یعنی گیرنده‌ای پیدا نشد و چیزی ارسال نشد
     *
     * @throws KavenegarException
     */
    public function send(object $notifiable, Notification $notification): ?array
    {
        if (! method_exists($notification, 'toKavenegar')) {
            throw InvalidMessageException::missingKavenegarMessage($notification::class);
        }

        $message = $notification->toKavenegar($notifiable);

        if (! $message instanceof KavenegarMessage) {
            throw InvalidMessageException::missingKavenegarMessage($notification::class);
        }

        $receptor = $message->receptor();

        if ($receptor === null && method_exists($notifiable, 'routeNotificationFor')) {
            $receptor = $notifiable->routeNotificationFor('kavenegar', $notification);
        }

        // رفتار استاندارد کانال‌های لاراول: بدون گیرنده، بی‌صدا رد می‌شود.
        if (! is_string($receptor) || trim($receptor) === '') {
            return null;
        }

        $gateway = $this->sms->driver($message->driver());

        return $message->isLookup()
            ? $gateway->lookup($message->toLookup($receptor))
            : $gateway->send($message->toSms($receptor));
    }
}
