<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\FileVersion;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller {
    public function create() {
        return view("settings.index");
    }

    public function update(Request $request) {
        $fields = $request->validate([
            "email" => [
                "required",
                "string",
                "email",
                "max:255",
                "unique:users",
            ],
        ]);

        /**
         * @var App\Models\User
         */
        $user = Auth::user();

        $fields = array_filter(
            $fields,
            function ($value, $key) use ($user) {
                return $user->{$key} !== $value;
            },
            ARRAY_FILTER_USE_BOTH
        );

        if (count($fields) === 0) {
            return Redirect::refresh()->with("snackbar", [
                "type" => "info",
                "message" => trans("Nothing to change."),
            ]);
        }

        $emailUpdated = array_key_exists("email", $fields);

        try {
            $user->fill($fields);

            if ($emailUpdated) {
                $user->email_verified_at = null;
            }

            $user->save();

            if ($emailUpdated) {
                $user->sendEmailVerificationNotification();
            }
        } catch (MassAssignmentException $e) {
            return Redirect::refresh()->with("snackbar", [
                "type" => "error",
                "message" => trans("Error updating user."),
            ]);
        }

        return Redirect::refresh()->with("snackbar", [
            "type" => "success",
            "message" => trans("User successfully updated."),
        ]);
    }

    public function destroy(Request $request) {
        $trashedFiles = $request
            ->user()
            ->files()
            ->withTrashed()
            ->pluck("uuid")
            ->toArray();
        $files = FileVersion::withTrashed()
            ->whereIn("file_uuid", $trashedFiles)
            ->pluck("path")
            ->toArray();

        if ($request->user()->delete()) {
            Storage::disk("files")->delete($files);

            Auth::guard("web")->logout();

            $request->session()->invalidate();

            $request->session()->regenerateToken();

            return redirect("login")->with("snackbar", [
                "type" => "success",
                "message" => trans("User was deleted successfully."),
            ]);
        }

        return Redirect::refresh()->with("snackbar", [
            "type" => "error",
            "message" => trans("User could not be deleted."),
        ]);
    }
}
