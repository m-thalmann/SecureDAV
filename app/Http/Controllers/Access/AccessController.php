<?php

namespace App\Http\Controllers\Access;

use App\Http\Controllers\Controller;
use App\Models\AccessUser;
use Illuminate\Support\Facades\Redirect;

class AccessController extends Controller {
    public function generateToken(AccessUser $accessUser) {
        $token = $accessUser->generateToken();

        return Redirect::back()->with("snackbar", [
            "type" => "success",
            "message" => trans("Created token") . ": $token",
            "duration" => null,
        ]);
    }
}
