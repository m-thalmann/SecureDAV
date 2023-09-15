<?php

namespace App\Auth\Fortify\Responses;

use Laravel\Fortify\Http\Responses\RecoveryCodesGeneratedResponse as BaseRecoveryCodesGeneratedResponse;

class RecoveryCodesGeneratedResponse extends
    BaseRecoveryCodesGeneratedResponse {
    public function toResponse($request) {
        return back()
            ->withFragment('two-factor-authentication')
            ->with('two-factor-recovery-codes-regenerated', true);
    }
}
