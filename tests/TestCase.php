<?php

namespace Fiachehr\Pardakht\Tests;

use Fiachehr\Pardakht\PardakhtServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    protected function getPackageProviders($app): array
    {
        return [
            PardakhtServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Pardakht' => \Fiachehr\Pardakht\Facades\Pardakht::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        // Set up test configuration
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Set up test gateway configurations
        $app['config']->set('pardakht.default', 'mellat');
        $app['config']->set('pardakht.store_transactions', true);

        $app['config']->set('pardakht.gateways.mellat', [
            'driver' => 'mellat',
            'terminal_id' => 'test_terminal',
            'username' => 'test_user',
            'password' => 'test_pass',
            'callback_url' => 'http://example.com/callback',
            'sandbox' => true,
        ]);

        $app['config']->set('pardakht.gateways.mabna', [
            'driver' => 'mabna',
            'terminal_id' => 'test_terminal',
            'callback_url' => 'http://example.com/callback',
            'sandbox' => true,
        ]);

        $app['config']->set('pardakht.gateways.zarinpal', [
            'driver' => 'zarinpal',
            'merchant_id' => 'test_merchant',
            'callback_url' => 'http://example.com/callback',
            'description' => 'Test Payment',
            'sandbox' => true,
        ]);
    }
}
