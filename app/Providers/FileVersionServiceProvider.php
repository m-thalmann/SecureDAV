<?php

namespace App\Providers;

use App\Services\EncryptionService;
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
    public function register(): void {
        $this->app->singleton(FileVersionService::class, function ($app) {
            return new FileVersionService(
                $app->make(EncryptionService::class),
                Storage::disk('files')
            );
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array {
        return [FileVersionService::class];
    }
}
