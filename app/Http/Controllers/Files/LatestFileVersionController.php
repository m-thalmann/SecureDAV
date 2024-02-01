<?php

namespace App\Http\Controllers\Files;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Services\FileVersionService;
use App\Support\SessionMessage;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\File as FileRule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LatestFileVersionController extends Controller {
    public function __construct() {
        $this->authorizeResource(File::class);

        $this->middleware('password.confirm')->only(['edit', 'update']);
    }

    public function show(
        File $file,
        FileVersionService $fileVersionService
    ): StreamedResponse|RedirectResponse {
        if ($file->latestVersion === null) {
            return $this->redirectNoLatestVersion($file);
        }

        $this->authorize('view', $file->latestVersion);

        return $fileVersionService->createDownloadResponse(
            $file,
            $file->latestVersion
        );
    }

    public function edit(File $file): View|RedirectResponse {
        if ($file->latestVersion === null) {
            return $this->redirectNoLatestVersion($file);
        }

        $this->authorize('update', $file->latestVersion);

        return view('file-versions.latest.edit', ['file' => $file]);
    }

    public function update(
        Request $request,
        FileVersionService $fileVersionService,
        File $file
    ): RedirectResponse {
        if ($file->latestVersion === null) {
            return $this->redirectNoLatestVersion($file);
        }

        $this->authorize('update', $file->latestVersion);

        $request->validate([
            'file' => [
                'required',
                FileRule::default()->max(config('core.files.max_file_size')),
            ],
        ]);

        $uploadedFile = $request->file('file');

        try {
            $versionUpdated = processResource(
                fopen($uploadedFile->path(), 'rb'),
                fn(
                    mixed $fileResource
                ) => $fileVersionService->updateLatestVersion(
                    $file,
                    $fileResource
                )
            );
        } catch (Exception $e) {
            return back()
                ->withInput()
                ->with(
                    'session-message',
                    SessionMessage::error(
                        __(
                            'An error occurred while updating the latest version.'
                        )
                    )
                );
        }

        return redirect()
            ->route('files.show', $file->uuid)
            ->withFragment('file-versions')
            ->with(
                'snackbar',
                SessionMessage::success(
                    $versionUpdated
                        ? __(
                            'The latest version of this file has been updated.'
                        )
                        : __(
                            'A new version of this file has been created (auto version).'
                        )
                )->forDuration()
            );
    }

    protected function redirectNoLatestVersion(File $file): RedirectResponse {
        return redirect()
            ->route('files.show', $file->uuid)
            ->withFragment('file-versions')
            ->with(
                'snackbar',
                SessionMessage::error(
                    __(
                        'This file doesn\'t have a version yet. Upload a file to create a new one.'
                    )
                )->forDuration()
            );
    }
}
