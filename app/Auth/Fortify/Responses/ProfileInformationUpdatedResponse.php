<?php

namespace App\Auth\Fortify\Responses;

use App\View\Helpers\SessionMessage;
use Laravel\Fortify\Http\Responses\ProfileInformationUpdatedResponse as BaseProfileInformationUpdatedResponse;

class ProfileInformationUpdatedResponse extends
    BaseProfileInformationUpdatedResponse {
    public function toResponse($request) {
        return back()
            ->withFragment('update-information')
            ->with(
                'snackbar',
                SessionMessage::success(__('Profile updated'), duration: 5)
            );
    }
}
