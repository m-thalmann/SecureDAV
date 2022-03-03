<?php

namespace App\Providers;

use App\Models\AccessUser;
use App\Models\AccessUserToken;
use App\Models\File;
use App\Models\FileVersion;
use App\Policies\AccessUserPolicy;
use App\Policies\AccessUserTokenPolicy;
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
        AccessUser::class => AccessUserPolicy::class,
        AccessUserToken::class => AccessUserTokenPolicy::class,
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
