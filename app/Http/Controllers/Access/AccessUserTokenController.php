<?php

namespace App\Http\Controllers\Access;

use App\Http\Controllers\Controller;
use App\Models\AccessUserToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class AccessUserTokenController extends Controller {
    public function updateActive(
        AccessUserToken $accessUserToken,
        Request $request
    ) {
        $request->validate([
            "active" => ["nullable"],
        ]);

        $accessUserToken->active = $request->boolean("active");
        $accessUserToken->save();

        return Redirect::back();
    }

    public function destroy(AccessUserToken $accessUserToken) {
        $accessUserToken->delete();

        return Redirect::route("access.details", [
            "accessUser" => $accessUserToken->access_user_id,
        ])->with("snackbar", [
            "type" => "success",
            "message" => trans("Token successfully deleted."),
        ]);
    }
}
