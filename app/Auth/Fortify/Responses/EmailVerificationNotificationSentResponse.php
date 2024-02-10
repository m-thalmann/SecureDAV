<?php

namespace App\Auth\Fortify\Responses;

use App\Support\SessionMessage;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Http\Responses\EmailVerificationNotificationSentResponse as BaseEmailVerificationNotificationSentResponse;

class EmailVerificationNotificationSentResponse extends
    BaseEmailVerificationNotificationSentResponse {
    public function toResponse(mixed $request): RedirectResponse {
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
