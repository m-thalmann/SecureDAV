<?php

namespace App\Auth\Fortify\Responses;

use App\Support\SessionMessage;
use Laravel\Fortify\Http\Responses\TwoFactorConfirmedResponse as BaseTwoFactorConfirmedResponse;

class TwoFactorConfirmedResponse extends BaseTwoFactorConfirmedResponse {
    public function toResponse($request) {
        return back()
            ->withFragment('two-factor-authentication')
            ->with('two-factor-confirmed', true)
            ->with(
                'snackbar',
                SessionMessage::success(
                    __('Two-factor authentication has been enabled.')
                )->forDuration()
            );
    }
}

