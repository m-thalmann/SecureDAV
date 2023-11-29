<?php

namespace App\Http\Controllers\Files;

use App\Http\Controllers\Controller;
use App\Models\Directory;
use App\Models\File;
use App\Support\SessionMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FileMoveController extends Controller {
    public function __construct() {
        $this->authorizeResource(File::class);

        $this->middleware('password.confirm')->only(['edit', 'update']);
    }

    public function edit(Request $request, File $file): View {
        $directoryUuid = $request->get('directory', null);

        $directory = $directoryUuid
            ? Directory::where('uuid', $directoryUuid)->firstOrFail()
            : null;

        if ($directory) {
            $this->authorize('update', $directory);
        }

        $directories = Directory::query()
            ->inDirectory($directory)
            ->ordered()
            ->get()
            ->all();

        $files = File::query()
            ->inDirectory($directory)
            ->ordered()
            ->get()
            ->all();

        $breadcrumbs = $directory ? $directory->breadcrumbs : [];

        $file->load('directory');

        return view('files.move', [
            'file' => $file,
            'currentDirectory' => $directory,
            'directories' => $directories,
            'files' => $files,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    public function update(Request $request, File $file): RedirectResponse {
        $directoryUuid = $request->get('directory_uuid', null);
        $directory = $directoryUuid
            ? Directory::where('uuid', $directoryUuid)->firstOrFail()
            : null;

        if ($directory) {
            $this->authorize('update', $directory);
        }

        try {
            $file->move($directory);
            $file->save();
        } catch (ValidationException $e) {
            return back()->with(
                'session-message',
                SessionMessage::error(
                    __('File name already exists in this directory.')
                )
            );
        }

        return redirect()
            ->route('files.show', ['file' => $file])
            ->with(
                'snackbar',
                SessionMessage::success(
                    __('File moved successfully.')
                )->forDuration()
            );
    }
}

