<?php

namespace App\Auth\Fortify\Responses;

use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Http\Responses\TwoFactorEnabledResponse as BaseTwoFactorEnabledResponse;

class TwoFactorEnabledResponse extends BaseTwoFactorEnabledResponse {
    public function toResponse(mixed $request): RedirectResponse {
        return back()->withFragment('two-factor-authentication');
    }
}
