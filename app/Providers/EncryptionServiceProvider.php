<?php

namespace App\Providers;

use App\Services\EncryptionService;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class EncryptionServiceProvider extends ServiceProvider implements
    DeferrableProvider {
    /**
     * Register services.
     *
     * @return void
     */
    public function register() {
        $this->app->singleton(EncryptionService::class, function ($app) {
            return new EncryptionService();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides() {
        return [EncryptionService::class];
    }
}

