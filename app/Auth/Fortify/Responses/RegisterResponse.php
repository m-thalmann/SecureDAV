<?php

namespace App\Auth\Fortify\Responses;

use App\Support\SessionMessage;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Http\Responses\RegisterResponse as BaseRegisterResponse;

class RegisterResponse extends BaseRegisterResponse {
    public function toResponse(mixed $request): RedirectResponse {
        $redirect = Fortify::redirects('register');

        if (config('app.email_verification_enabled')) {
            $redirect = route('verification.notice', absolute: false);
        }

        return redirect($redirect)->with(
            'snackbar',
            SessionMessage::success(
                __('Registration successful.')
            )->forDuration()
        );
    }
}
