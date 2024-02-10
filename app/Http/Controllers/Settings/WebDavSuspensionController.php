<?php

namespace App\Http\Controllers\Settings;

use App\Events\WebDavResumed;
use App\Events\WebDavSuspended;
use App\Http\Controllers\Controller;
use App\Support\SessionMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WebDavSuspensionController extends Controller {
    public function __invoke(Request $request): RedirectResponse {
        $suspended = $request->boolean('suspended');

        $user = authUser();
        $user->is_webdav_suspended = $suspended;

        $user->save();

        if ($suspended) {
            event(new WebDavSuspended($user));
        } else {
            event(new WebDavResumed($user));
        }

        return back()->with(
            'snackbar',
            SessionMessage::success(
                __(
                    'WebDAV successfully ' .
                        ($suspended ? 'suspended' : 'resumed')
                )
            )->forDuration()
        );
    }
}
