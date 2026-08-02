<?php

namespace Fiachehr\Pardakht;

use Fiachehr\Pardakht\Contracts\TransactionRepositoryInterface;
use Fiachehr\Pardakht\Manager\GatewayManager;
use Fiachehr\Pardakht\Models\Transaction;
use Fiachehr\Pardakht\Repositories\TransactionRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Class PardakhtServiceProvider
 *
 * Service provider for the Pardakht package
 */
class PardakhtServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/pardakht.php',
            'pardakht'
        );

        $this->app->bind(TransactionRepositoryInterface::class, function ($app) {
            return new TransactionRepository(new Transaction());
        });

        $this->app->singleton('pardakht', function ($app) {
            $repository = null;

            if (config('pardakht.store_transactions', true)) {
                $repository = $app->make(TransactionRepositoryInterface::class);
            }

            return new GatewayManager($repository);
        });

        $this->app->alias('pardakht', GatewayManager::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/pardakht.php' => config_path('pardakht.php'),
            ], 'pardakht-config');

            $this->publishes([
                __DIR__ . '/../database/migrations/' => database_path('migrations'),
            ], 'pardakht-migrations');

            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [
            'pardakht',
            GatewayManager::class,
            TransactionRepositoryInterface::class,
        ];
    }
}
