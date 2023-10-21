<?php

namespace App\Auth\Fortify\Responses;

use App\Support\SessionMessage;
use Laravel\Fortify\Http\Responses\SuccessfulPasswordResetLinkRequestResponse as BaseSuccessfulPasswordResetLinkRequestResponse;

class SuccessfulPasswordResetLinkRequestResponse extends
    BaseSuccessfulPasswordResetLinkRequestResponse {
    public function toResponse($request) {
        return back()->with(
            'session-message',
            SessionMessage::success(__($this->status))
        );
    }
}
