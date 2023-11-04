<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Support\SessionMessage;
use Illuminate\Http\Request;

class WebDavSuspensionController extends Controller {
    public function __invoke(Request $request) {
        $suspended = $request->boolean('suspended');

        $user = authUser();
        $user->is_webdav_suspended = $suspended;

        $user->save();

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

