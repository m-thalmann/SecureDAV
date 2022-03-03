<?php

namespace App\Http\Controllers\Files;

use App\Http\Controllers\Controller;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AddFileController extends Controller {
    public function store(Request $request) {
        $request->validate([
            "name" => [
                "nullable",
                Rule::unique("files", "display_name")->where(function ($query) {
                    return $query->where("user_id", Auth::user()->id);
                }),
            ],
            "file" => ["required", "file"],
            "encrypt" => ["nullable"],
        ]);

        if (!$request->filled("name")) {
            Validator::make(
                ["name" => $request->file("file")->getClientOriginalName()],
                ["name" => "unique:files,display_name"]
            )->validate();
        }

        $file = File::upload(
            Auth::user()->id,
            $request->file("file"),
            $request->boolean("encrypt", false),
            $request->input("name", null)
        );

        return Redirect::route("files.details", ["file" => $file->uuid])->with(
            "snackbar",
            [
                "type" => "success",
                "message" => trans("File uploaded successfully."),
            ]
        );
    }
}
