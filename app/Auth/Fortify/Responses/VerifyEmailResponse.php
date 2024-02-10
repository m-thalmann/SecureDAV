<?php

namespace App\Auth\Fortify\Responses;

use App\Support\SessionMessage;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Http\Responses\VerifyEmailResponse as BaseVerifyEmailResponse;

class VerifyEmailResponse extends BaseVerifyEmailResponse {
    public function toResponse(mixed $request): RedirectResponse {
        return redirect()
            ->intended(Fortify::redirects('email-verification'))
            ->with(
                'snackbar',
                SessionMessage::success(
                    __('Your email has been verified')
                )->forDuration()
            );
    }
}
