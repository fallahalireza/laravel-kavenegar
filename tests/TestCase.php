<?php

declare(strict_types=1);

namespace FallahAlireza\Kavenegar\Tests;

use FallahAlireza\Kavenegar\KavenegarServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [KavenegarServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('kavenegar.default', 'kavenegar');
        $app['config']->set('kavenegar.drivers.kavenegar.api_key', 'SECRET-KEY');
        $app['config']->set('kavenegar.drivers.kavenegar.sender', '10004346');
    }
}
