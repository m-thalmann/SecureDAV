<?php

namespace App\Http\Controllers\Files;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\FileVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class FileVersionsController extends Controller {
    public function createTrash(File $file) {
        return view("files.versions.trash", ["file" => $file]);
    }

    public function download(File $file, FileVersion $version) {
        return $version->download();
    }

    public function store(File $file) {
        $version = FileVersion::createVersion($file->uuid);

        if ($version !== null) {
            $snackbar = [
                "type" => "success",
                "message" => trans("Version created."),
            ];
        } else {
            $snackbar = [
                "type" => "error",
                "message" => trans("Version could not be created."),
            ];
        }

        return Redirect::route("files.details", ["file" => $file->uuid])->with(
            "snackbar",
            $snackbar
        );
    }

    public function createUpload(File $file) {
        return view("files.versions.upload", ["file" => $file]);
    }

    public function upload(File $file, Request $request) {
        $request->validate([
            "file" => ["required", "file"],
            "new_version" => ["nullable"],
        ]);

        try {
            $file->uploadFile(
                $request->file("file"),
                $request->boolean("new_version")
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors([
                "type" => trans(
                    "The mime-types and/or extensions of the files do not match."
                ),
            ]);
        }

        return Redirect::route("files.details", ["file" => $file->uuid])->with(
            "snackbar",
            [
                "type" => "success",
                "message" => trans("File uploaded successfully."),
            ]
        );
    }

    public function delete(File $file, FileVersion $version) {
        $version->delete();

        return Redirect::route("files.details", ["file" => $file->uuid])->with(
            "snackbar",
            [
                "type" => "success",
                "message" => trans("Version successfully moved to trash"),
            ]
        );
    }

    public function deleteFromTrash(FileVersion $version) {
        $file = $version->file_uuid;

        if ($version->forceDelete()) {
            $version->deleteFile();

            $snackbar = [
                "type" => "success",
                "message" => trans("Version successfully deleted."),
            ];
        } else {
            $snackbar = [
                "type" => "error",
                "message" => trans("Version could not be deleted."),
            ];
        }

        return Redirect::route("files.versions.trash", [
            "file" => $file,
        ])->with("snackbar", $snackbar);
    }

    public function restoreFromTrash(FileVersion $version) {
        $version->restore();

        return Redirect::route("files.versions.trash", [
            "file" => $version->file_uuid,
        ])->with("snackbar", [
            "type" => "success",
            "message" => trans("Version successfully restored."),
        ]);
    }

    public function clearTrash(File $file) {
        $query = FileVersion::onlyTrashed()->where("file_uuid", $file->uuid);

        $paths = $query->pluck("path")->toArray();
        $query->forceDelete();

        if (count($paths) > 0) {
            Storage::disk("files")->delete($paths);
        }

        return Redirect::route("files.versions.trash", [
            "file" => $file->uuid,
        ])->with("snackbar", [
            "type" => "success",
            "message" => trans("Trash successfully cleared."),
        ]);
    }
}
