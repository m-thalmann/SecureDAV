<?php

namespace App\Auth\Fortify\Responses;

use App\View\Helpers\SessionMessage;
use Laravel\Fortify\Http\Responses\EmailVerificationNotificationSentResponse as BaseEmailVerificationNotificationSentResponse;

class EmailVerificationNotificationSentResponse extends
    BaseEmailVerificationNotificationSentResponse {
    public function toResponse($request) {
        return back()->with(
            'session-message',
            SessionMessage::success(
                __(
                    'A new verification link has been sent to the email address you\'ve provided.'
                )
            )
        );
    }
}
