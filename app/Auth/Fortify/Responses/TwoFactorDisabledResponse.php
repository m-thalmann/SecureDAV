<?php

namespace App\Auth\Fortify\Responses;

use App\Support\SessionMessage;
use Laravel\Fortify\Http\Responses\TwoFactorDisabledResponse as BaseTwoFactorDisabledResponse;

class TwoFactorDisabledResponse extends BaseTwoFactorDisabledResponse {
    public function toResponse($request) {
        return back()
            ->withFragment('two-factor-authentication')
            ->with(
                'snackbar',
                SessionMessage::success(
                    __('Two-factor authentication has been disabled.')
                )->forDuration()
            );
    }
}

