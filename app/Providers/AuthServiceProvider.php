<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider {
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\File::class => \App\Policies\FilePolicy::class,
        \App\Models\Directory::class => \App\Policies\DirectoryPolicy::class,
        \App\Models\FileVersion::class =>
            \App\Policies\FileVersionPolicy::class,
        \App\Models\AccessGroup::class =>
            \App\Policies\AccessGroupPolicy::class,
        \App\Models\AccessGroupUser::class =>
            \App\Policies\AccessGroupUserPolicy::class,
        \Illuminate\Notifications\DatabaseNotification::class =>
            \App\Policies\NotificationPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void {
    }
}
