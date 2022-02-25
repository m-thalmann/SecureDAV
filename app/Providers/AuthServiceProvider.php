<?php

namespace App\Providers;

use App\Models\File;
use App\Models\FileVersion;
use App\Policies\FilePolicy;
use App\Policies\FileVersionPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider {
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        File::class => FilePolicy::class,
        FileVersion::class => FileVersionPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot() {
        $this->registerPolicies();
    }
}
