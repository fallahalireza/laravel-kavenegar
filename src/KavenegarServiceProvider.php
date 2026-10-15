<?php

declare(strict_types=1);

namespace FallahAlireza\Kavenegar;

use FallahAlireza\Kavenegar\Contracts\SmsGateway;
use FallahAlireza\Kavenegar\Notifications\KavenegarChannel;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;

class KavenegarServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/kavenegar.php', 'kavenegar');

        $this->app->singleton(SmsManager::class, fn ($app): SmsManager => new SmsManager($app));

        // تزریق SmsGateway در هر کلاسی، همان Manager (درایور پیش‌فرض) را می‌دهد.
        $this->app->alias(SmsManager::class, SmsGateway::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/kavenegar.php' => config_path('kavenegar.php'),
            ], 'kavenegar-config');
        }

        Notification::resolved(function ($service): void {
            $service->extend('kavenegar', fn ($app) => $app->make(KavenegarChannel::class));
        });
    }
}
