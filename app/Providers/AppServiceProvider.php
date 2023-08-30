<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
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
    }

    protected function definePasswordRules() {
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
