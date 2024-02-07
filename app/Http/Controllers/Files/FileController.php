<?php

namespace App\Http\Controllers\Files;

use App\Http\Controllers\Controller;
use App\Models\Directory;
use App\Models\File;
use App\Rules\NotStringContains;
use App\Rules\UniqueFileName;
use App\Services\FileVersionService;
use App\Support\SessionMessage;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\File as FileRule;
use Illuminate\View\View;

class FileController extends Controller {
    public function __construct() {
        $this->authorizeResource(File::class);

        $this->middleware('password.confirm')->only([
            'edit',
            'update',
            'updateAutoVersionHours',
            'destroy',
        ]);
    }

    public function create(Request $request): View {
        $directoryUuid = $request->get('directory', null);
        $directory = $directoryUuid
            ? Directory::where('uuid', $directoryUuid)->firstOrFail()
            : null;

        if ($directory) {
            $this->authorize('update', $directory);
        }

        return view('files.create', [
            'directory' => $directory,
        ]);
    }

    public function store(
        Request $request,
        FileVersionService $fileVersionService
    ): RedirectResponse {
        $directoryUuid = $request->get('directory_uuid', null);
        $directory = $directoryUuid
            ? Directory::where('uuid', $directoryUuid)->firstOrFail()
            : null;

        if ($directory) {
            $this->authorize('update', $directory);
        }

        $initialize = $request->get('initialize', 'true') === 'true';
        $requestFile = null;

        if ($initialize) {
            $request->validate([
                'file' => [
                    'required',
                    FileRule::default()->max(
                        config('core.files.max_file_size_bytes') / 1000 // must be KB
                    ),
                ],
            ]);

            $requestFile = $request->file('file');
        }

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:' . config('core.files.max_name_length'),
                new NotStringContains(config('core.files.illegal_characters')),
                new UniqueFileName(
                    authUser()->id,
                    inDirectoryId: $directory?->id
                ),
            ],

            'encrypt' => ['nullable'],

            'description' => ['nullable', 'string', 'max:512'],
        ]);

        $isEncrypted = !!Arr::get($data, 'encrypt', false);

        try {
            DB::beginTransaction();

            $file = authUser()
                ->files()
                ->make()
                ->forceFill([
                    'directory_id' => $directory?->id,
                    'name' => $data['name'],
                    'description' => $data['description'] ?? null,
                ]);

            $file->save();

            if ($initialize) {
                processResource(fopen($requestFile->path(), 'rb'), function (
                    mixed $fileResource
                ) use ($fileVersionService, $file, $isEncrypted) {
                    $fileVersionService->createNewVersion(
                        $file,
                        $fileResource,
                        $isEncrypted
                    );
                });
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with(
                    'session-message',
                    SessionMessage::error(__('File could not be created'))
                );
        }

        return redirect()
            ->route('files.show', $file->uuid)
            ->with(
                'snackbar',
                SessionMessage::success(
                    __('File created successfully')
                )->forDuration()
            );
    }

    public function show(File $file): View {
        $file
            ->load('latestVersion')
            ->load('versions')
            ->load('webDavUsers')
            ->load('backupConfigurations');

        return view('files.show', [
            'file' => $file,
        ]);
    }

    public function edit(File $file): View {
        return view('files.edit', ['file' => $file]);
    }

    public function update(Request $request, File $file): RedirectResponse {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:' . config('core.files.max_name_length'),
                new NotStringContains(config('core.files.illegal_characters')),
                new UniqueFileName(
                    $file->user_id,
                    inDirectoryId: $file->directory_id,
                    ignoreFile: $file
                ),
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

    public function updateAutoVersionHours(
        Request $request,
        File $file
    ): RedirectResponse {
        $this->authorize('update', $file);

        $data = $request->validate([
            'hours' => ['nullable', 'decimal:0,1', 'min:0.1'],
        ]);

        $file->update([
            'auto_version_hours' => $data['hours'],
        ]);

        return back()->with(
            'snackbar',
            SessionMessage::success(
                __('Auto versioning time updated successfully.')
            )->forDuration()
        );
    }

    public function destroy(File $file): RedirectResponse {
        $directory = $file->directory;

        $file->delete();

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
