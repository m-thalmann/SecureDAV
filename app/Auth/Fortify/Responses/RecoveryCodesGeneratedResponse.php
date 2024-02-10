<?php

namespace App\Auth\Fortify\Responses;

use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Http\Responses\RecoveryCodesGeneratedResponse as BaseRecoveryCodesGeneratedResponse;

class RecoveryCodesGeneratedResponse extends
    BaseRecoveryCodesGeneratedResponse {
    public function toResponse(mixed $request): RedirectResponse {
        return back()
            ->withFragment('two-factor-authentication')
            ->with('two-factor-recovery-codes-regenerated', true);
    }
}
