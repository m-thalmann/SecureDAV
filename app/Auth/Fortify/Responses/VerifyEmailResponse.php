<?php

namespace App\Auth\Fortify\Responses;

use App\View\Helpers\SessionMessage;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Http\Responses\VerifyEmailResponse as BaseVerifyEmailResponse;

class VerifyEmailResponse extends BaseVerifyEmailResponse {
    public function toResponse($request) {
        return redirect()
            ->intended(Fortify::redirects('email-verification'))
            ->with(
                'snackbar',
                SessionMessage::success(
                    __('Your email has been verified'),
                    duration: 5
                )
            );
    }
}
