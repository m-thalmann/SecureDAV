<?php

namespace App\Providers;

use App\Services\FileEncryptionService;
use App\Services\FileVersionService;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;

class FileVersionServiceProvider extends ServiceProvider implements
    DeferrableProvider {
    /**
     * Register services.
     *
     * @return void
     */
    public function register() {
        $this->app->singleton(FileVersionService::class, function ($app) {
            return new FileVersionService(
                $app->make(FileEncryptionService::class),
                Storage::disk('files')
            );
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides() {
        return [FileVersionService::class];
    }
}
