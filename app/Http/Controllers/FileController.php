<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\View\Helpers\SessionMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FileController extends Controller {
    public function __construct() {
        $this->authorizeResource(File::class);
    }

    public function show(File $file): View {
        return view('files.show', ['file' => $file]);
    }

    public function destroy(File $file): RedirectResponse {
        // TODO: confirm password

        $directory = $file->directory;

        $deleteSuccessful = $file->delete();

        if ($deleteSuccessful && $directory !== null) {
            File::withTrashed()
                ->find($file->id)
                ->update(['directory_id' => null]);
        }

        return redirect()
            ->route('browse.index', $directory?->uuid)
            ->with(
                'snackbar',
                SessionMessage::success(
                    __('File successfully moved to trash')
                )->forDuration()
            );
    }
}

