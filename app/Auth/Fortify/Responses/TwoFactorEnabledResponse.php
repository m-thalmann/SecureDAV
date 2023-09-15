<?php

namespace App\Auth\Fortify\Responses;

use Laravel\Fortify\Http\Responses\TwoFactorEnabledResponse as BaseTwoFactorEnabledResponse;

class TwoFactorEnabledResponse extends BaseTwoFactorEnabledResponse {
    public function toResponse($request) {
        return back()->withFragment('two-factor-authentication');
    }
}
