<?php

namespace App\Http\Controllers;

use App\Exceptions\NoVersionFoundException;
use App\Models\File;
use App\Models\FileVersion;
use App\Services\FileVersionService;
use App\View\Helpers\SessionMessage;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\File as FileRule;
use Illuminate\View\View;

class FileVersionController extends Controller {
    public function __construct() {
        $this->authorizeResource(FileVersion::class);
    }

    public function create(File $file): View {
        $this->authorize('update', $file);

        return view('file-versions.create', ['file' => $file]);
    }

    public function store(
        Request $request,
        FileVersionService $fileVersionService,
        File $file
    ): RedirectResponse {
        $this->authorize('update', $file);

        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:64'],
            'file' => ['nullable', FileRule::default()->max('1gb')],
        ]);

        $uploadedFile = $request->file('file');

        try {
            if ($uploadedFile !== null) {
                $fileVersionService->createNewVersion(
                    $file,
                    $uploadedFile,
                    $data['label'] ?? null
                );
            } else {
                $fileVersionService->copyLatestVersion(
                    $file,
                    $data['label'] ?? null
                );
            }
        } catch (NoVersionFoundException $e) {
            return back()->with(
                'snackbar',
                SessionMessage::warning(
                    __(
                        'This file doesn\'t have a version yet. Upload a file to create a new one.'
                    )
                )->forDuration()
            );
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
            ->withFragment('file-versions')
            ->with(
                'snackbar',
                SessionMessage::success(
                    __('A new version of this file has been created.')
                )->forDuration()
            );
    }

    public function edit(FileVersion $fileVersion): View {
        return view('file-versions.edit', ['fileVersion' => $fileVersion]);
    }

    public function update(
        Request $request,
        FileVersion $fileVersion
    ): RedirectResponse {
        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:64'],
        ]);

        $fileVersion->update($data);

        return redirect()
            ->route('files.show', $fileVersion->file->uuid)
            ->withFragment('file-versions')
            ->with(
                'snackbar',
                SessionMessage::success(
                    __('File version updated successfully')
                )->forDuration()
            );
    }

    public function destroy(FileVersion $fileVersion): RedirectResponse {
        $file = $fileVersion->file;

        $fileVersion->delete();

        return redirect()
            ->route('files.show', $file->uuid)
            ->withFragment('file-versions')
            ->with(
                'snackbar',
                SessionMessage::success(
                    __('Version successfully moved to trash.')
                )->forDuration()
            );
    }
}

