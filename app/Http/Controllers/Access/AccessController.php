<?php

namespace App\Http\Controllers\Access;

use App\Http\Controllers\Controller;
use App\Models\AccessUser;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

class AccessController extends Controller {
    public function storeUser(Request $request) {
        $request->validate([
            "label" => ["nullable"],
            "access_all" => ["nullable"],
            "readonly" => ["nullable"],
        ]);

        $accessUser = new AccessUser();
        $accessUser->fill([
            "user_id" => Auth::user()->id,
            "username" => AccessUser::generateUsername(),
            "readonly" => $request->boolean("readonly"),
            "access_all" => $request->boolean("access_all"),
        ]);
        $accessUser->save();

        return Redirect::route("access.details", [
            "accessUser" => $accessUser->id,
        ])->with("snackbar", [
            "type" => "success",
            "message" => trans("Access-user created successfully."),
        ]);
    }

    public function createDetails(AccessUser $accessUser) {
        return view("access.details", ["accessUser" => $accessUser]);
    }

    public function generateToken(AccessUser $accessUser) {
        $token = $accessUser->generateToken();

        return Redirect::back()->with("snackbar", [
            "type" => "success",
            "message" => trans("Created token") . ": $token",
            "duration" => null,
        ]);
    }

    public function updateReadOnly(AccessUser $accessUser, Request $request) {
        $request->validate([
            "readonly" => ["nullable"],
        ]);

        $accessUser->readonly = $request->boolean("readonly");
        $accessUser->save();

        return Redirect::back();
    }

    public function updateAccessAll(AccessUser $accessUser, Request $request) {
        $request->validate([
            "access_all" => ["nullable"],
        ]);

        $accessUser->access_all = $request->boolean("access_all");
        $accessUser->save();

        return Redirect::back();
    }

    public function revokeFileAccess(AccessUser $accessUser, $file) {
        DB::table("access_user_files")
            ->where("access_user_id", $accessUser->id)
            ->where("file_uuid", $file)
            ->delete();

        return Redirect::back()->with("snackbar", [
            "type" => "success",
            "message" => trans("File-access revoked successfully!"),
        ]);
    }
}
