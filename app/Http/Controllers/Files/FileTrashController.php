<?php

namespace App\Http\Controllers\Files;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Support\SessionMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FileTrashController extends Controller {
    public function index(): View {
        $files = authUser()
            ->files()
            ->with('directory')
            ->onlyTrashed()
            ->paginate(perPage: 10);

        return view('files.trash', [
            'files' => $files,
        ]);
    }

    public function restore(File $file): RedirectResponse {
        $this->authorize('restore', $file);

        $data = request()->validate([
            'rename' => [
                'nullable',
                'string',
                'max:' . config('core.files.max_name_length'),
            ],
        ]);

        $rename = $data['rename'] ?? null;

        if ($rename) {
            $file->name = $rename;
        }

        try {
            $file->move($file->directory);
            $file->restore();
        } catch (ValidationException $e) {
            return back()->with(
                'snackbar',
                SessionMessage::error(
                    __(
                        'A file with the same name already exists in the target directory.'
                    )
                )->forDuration()
            );
        }

        return redirect()
            ->route('files.trash.index')
            ->with(
                'snackbar',
                SessionMessage::success(
                    __('File successfully restored.')
                )->forDuration()
            );
    }

    public function destroy(File $file): RedirectResponse {
        $this->authorize('forceDelete', $file);

        $file->forceDelete();

        return back()->with(
            'snackbar',
            SessionMessage::success(
                __('File deleted permanently.')
            )->forDuration()
        );
    }
}

