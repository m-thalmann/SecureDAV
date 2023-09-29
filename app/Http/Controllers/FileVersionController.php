<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\FileVersion;
use App\View\Helpers\SessionMessage;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileVersionController extends Controller {
    public function __construct() {
        $this->authorizeResource(FileVersion::class);
    }

    public function store(Request $request, File $file): RedirectResponse {
        $this->authorize('update', $file);

        $latestVersion = $file
            ->versions()
            ->latest()
            ->first();

        if ($latestVersion === null) {
            return back()->with(
                'snackbar',
                SessionMessage::warning(
                    __(
                        'This file doesn\'t have a version yet. Upload a file to create a new one.'
                    )
                )->forDuration()
            );
        }

        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:64'],
        ]);

        $newPath = Str::uuid()->toString();

        try {
            if (Storage::disk('files')->exists($newPath)) {
                throw new Exception('Path already exists. Try again');
            }

            DB::transaction(function () use (
                $file,
                $latestVersion,
                $newPath,
                $data
            ) {
                $newVersion = new FileVersion();

                $newVersion->forceFill([
                    'file_id' => $file->id,
                    'label' => $data['label'] ?? null,
                    'version' => $file->next_version,
                    'storage_path' => $newPath,
                    'etag' => $latestVersion->etag,
                    'bytes' => $latestVersion->bytes,
                ]);

                $newVersion->save();

                $nextVersionSetSuccessfully = $file
                    ->forceFill([
                        'next_version' => $file->next_version + 1,
                    ])
                    ->save();

                if (!$nextVersionSetSuccessfully) {
                    throw new Exception('Next version could not be set');
                }

                $fileCopiedSuccessfully = Storage::disk('files')->copy(
                    $latestVersion->storage_path,
                    $newPath
                );

                if (!$fileCopiedSuccessfully) {
                    throw new Exception('File could not be copied');
                }
            });
        } catch (Exception $e) {
            return back()->with(
                'snackbar',
                SessionMessage::error(
                    __(
                        'An error occurred while creating a new version of this file.'
                    )
                )->forDuration()
            );
        }

        return redirect()
            ->route('files.show', $file->uuid)
            ->with(
                'snackbar',
                SessionMessage::success(
                    __('A new version of this file has been created.')
                )->forDuration()
            );
    }

    public function destroy(FileVersion $fileVersion): RedirectResponse {
        $file = $fileVersion->file;

        $fileVersion->delete();

        return redirect()
            ->route('files.show', $file->uuid)
            ->with(
                'snackbar',
                SessionMessage::success(
                    __('Version successfully moved to trash.')
                )->forDuration()
            );
    }
}

