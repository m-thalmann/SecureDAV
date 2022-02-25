<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider {
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {
        if (App::isProduction()) {
            Password::defaults(function () {
                return Password::min(8)
                    ->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols();
            });
        } else {
            Password::defaults(function () {
                return Password::min(3);
            });
        }

        // Carbon::setLocale(config("app.locale"));
    }
}
