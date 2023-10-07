<?php

namespace App\Http\Controllers;

use App\Exceptions\MimeTypeMismatchException;
use App\Models\File;
use App\Services\FileVersionService;
use App\View\Helpers\SessionMessage;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\File as FileRule;

class LatestFileVersionController extends Controller {
    public function __construct() {
        $this->authorizeResource(File::class);
    }

    public function show(File $file, FileVersionService $fileVersionService) {
        if ($file->latestVersion === null) {
            return $this->redirectNoLatestVersion($file);
        }

        $this->authorize('view', $file->latestVersion);

        return $fileVersionService->createDownloadResponse(
            $file,
            $file->latestVersion
        );
    }

    public function edit(File $file) {
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
        // TODO: confirm password

        if ($file->latestVersion === null) {
            return $this->redirectNoLatestVersion($file);
        }

        $this->authorize('update', $file->latestVersion);

        $request->validate([
            'file' => ['required', FileRule::default()->max('1gb')],
        ]);

        $uploadedFile = $request->file('file');

        try {
            $fileVersionService->updateLatestVersion($file, $uploadedFile);
        } catch (MimeTypeMismatchException $e) {
            return back()
                ->withInput()
                ->withErrors([
                    'file' => __('The uploaded file has the wrong type.'),
                ]);
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
                    __('A new version of this file has been created.')
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
                )
            );
    }
}

