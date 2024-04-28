<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider {
    /**
     * Register any application services.
     */
    public function register(): void {
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {
        $this->definePasswordRules();

        Model::preventSilentlyDiscardingAttributes(!app()->isProduction());

        Paginator::defaultView('components.pagination');

        if (config('app.force_https', false)) {
            URL::forceScheme('https');
        }
    }

    protected function definePasswordRules(): void {
        Password::defaults(function () {
            if (app()->isProduction()) {
                return Password::min(8)
                    ->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols();
            } else {
                return Password::min(3);
            }
        });
    }
}
