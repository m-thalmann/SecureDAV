<?php

namespace App\Auth\Fortify\Responses;

use App\View\Helpers\SessionMessage;
use Laravel\Fortify\Http\Responses\ProfileInformationUpdatedResponse as BaseProfileInformationUpdatedResponse;

class ProfileInformationUpdatedResponse extends
    BaseProfileInformationUpdatedResponse {
    public function toResponse($request) {
        return back()->with(
            'session-message[update-profile-information]',
            SessionMessage::success(__('Profile updated'))
        );
    }
}
