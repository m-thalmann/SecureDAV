<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\View\Helpers\SessionMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FileController extends Controller {
    public function __construct() {
        $this->authorizeResource(File::class);
    }

    public function show(File $file): View {
        return view('files.show', ['file' => $file]);
    }

    public function edit(File $file): View {
        return view('files.edit', ['file' => $file]);
    }

    public function update(Request $request, File $file): RedirectResponse {
        // TODO: make directory_id editable

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:128',
                Rule::unique('files', 'name')
                    ->where('directory_id', $file->directory_id)
                    ->where('user_id', $file->user_id)
                    ->ignore($file),
            ],

            'description' => ['nullable', 'string', 'max:512'],
        ]);

        $file->update($data);

        return redirect()
            ->route('files.show', $file->uuid)
            ->with(
                'snackbar',
                SessionMessage::success(
                    __('File updated successfully')
                )->forDuration()
            );
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

