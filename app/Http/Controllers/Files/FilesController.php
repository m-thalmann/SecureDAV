<?php

namespace App\Http\Controllers\Files;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\FileVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;

class FilesController extends Controller {
    public function delete(File $file) {
        $file->delete();

        return Redirect::route("files")->with("snackbar", [
            "type" => "success",
            "message" => trans("File successfully moved to trash"),
        ]);
    }

    public function create() {
        return view("files.index");
    }

    public function createDetails(File $file) {
        return view("files.details", ["file" => $file]);
    }

    public function createTrash() {
        return view("files.trash");
    }

    public function clearTrash(Request $request) {
        $trashedFiles = $request
            ->user()
            ->files()
            ->onlyTrashed()
            ->pluck("uuid")
            ->toArray();
        $files = FileVersion::withTrashed()
            ->whereIn("file_uuid", $trashedFiles)
            ->pluck("path")
            ->toArray();

        if (File::onlyTrashed()->forceDelete()) {
            Storage::disk("files")->delete($files);

            return Redirect::route("files.trash")->with("snackbar", [
                "type" => "success",
                "message" => trans("Trash successfully cleared."),
            ]);
        }

        return Redirect::refresh()->with("snackbar", [
            "type" => "error",
            "message" => trans("Trash could not be cleared."),
        ]);
    }

    public function deleteFromTrash(File $file) {
        $paths = FileVersion::getFilePaths($file->uuid, true);

        $file->forceDelete();

        Storage::disk("files")->delete($paths);

        return Redirect::route("files.trash")->with("snackbar", [
            "type" => "success",
            "message" => trans("File successfully deleted."),
        ]);
    }

    public function restoreFromTrash(File $file) {
        $file->restore();

        return Redirect::route("files.trash")->with("snackbar", [
            "type" => "success",
            "message" => trans("File successfully restored."),
        ]);
    }
}
// TODO: unify controller function names (delete <-> destroy, create <-> view)
