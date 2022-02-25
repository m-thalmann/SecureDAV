<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rules;

class SettingsPasswordController extends Controller {
    public function create() {
        return view("settings.password");
    }

    public function update(Request $request) {
        $request->validate([
            "current_password" => ["required", "current_password"],
            "password" => ["required", "confirmed", Rules\Password::defaults()],
        ]);

        /**
         * @var App\Model\User
         */
        $user = Auth::user();

        $user->password = Hash::make($request->input("password"));
        $user->save();

        return Redirect::route("settings")->with("snackbar", [
            "type" => "success",
            "message" => trans("Password was set successfully."),
        ]);
    }
}
