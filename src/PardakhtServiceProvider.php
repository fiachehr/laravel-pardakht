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
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../config/pardakht.php',
            'pardakht'
        );

        // Register Transaction Repository
        $this->app->bind(TransactionRepositoryInterface::class, function ($app) {
            return new TransactionRepository(new Transaction());
        });

        // Register Gateway Manager as singleton
        $this->app->singleton('pardakht', function ($app) {
            $repository = null;

            if (config('pardakht.store_transactions', true)) {
                $repository = $app->make(TransactionRepositoryInterface::class);
            }

            return new GatewayManager($repository);
        });

        // Alias for easier access
        $this->app->alias('pardakht', GatewayManager::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Publish configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/pardakht.php' => config_path('pardakht.php'),
            ], 'pardakht-config');

            // Publish migrations
            $this->publishes([
                __DIR__ . '/../database/migrations/' => database_path('migrations'),
            ], 'pardakht-migrations');

            // Load migrations
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
