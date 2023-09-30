<?php

namespace App\Providers;

use App\Services\FileEncryptionService;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class FileEncryptionServiceProvider extends ServiceProvider implements
    DeferrableProvider {
    /**
     * Register services.
     *
     * @return void
     */
    public function register() {
        $this->app->singleton(FileEncryptionService::class, function ($app) {
            return new FileEncryptionService();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides() {
        return [FileEncryptionService::class];
    }
}
