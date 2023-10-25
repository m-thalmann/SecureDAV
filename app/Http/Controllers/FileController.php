<?php

namespace App\Http\Controllers;

use App\Models\Directory;
use App\Models\File;
use App\Services\FileVersionService;
use App\Support\SessionMessage;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File as FileRule;
use Illuminate\View\View;

class FileController extends Controller {
    public function __construct() {
        $this->authorizeResource(File::class);
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

        $request->validate([
            'file' => ['required', FileRule::default()->max('1gb')],
        ]);

        $requestFile = $request->file('file');

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:128',
                Rule::unique('files', 'name')
                    ->where('directory_id', $directory?->id)
                    ->where('user_id', authUser()->id)
                    ->withoutTrashed(),
                Rule::unique('directories', 'name')
                    ->where('parent_directory_id', $directory?->id)
                    ->where('user_id', authUser()->id),
            ],

            'encrypt' => ['nullable'],

            'description' => ['nullable', 'string', 'max:512'],
        ]);

        $encryptionKey = Arr::get($data, 'encrypt', false)
            ? Str::random(16)
            : null;

        $file = authUser()
            ->files()
            ->make()
            ->forceFill([
                'directory_id' => $directory?->id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'mime_type' => $requestFile->getClientMimeType(),
                'encryption_key' => $encryptionKey,
            ]);

        try {
            DB::transaction(function () use (
                $fileVersionService,
                $file,
                $requestFile
            ) {
                $file->save();

                $fileVersionService->createNewVersion($file, $requestFile);
            });
        } catch (Exception $e) {
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
            ->load('accessGroups');
        $file->accessGroups->loadCount('users');

        return view('files.show', [
            'file' => $file,
        ]);
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
                    ->ignore($file)
                    ->withoutTrashed(),
                Rule::unique('directories', 'name')
                    ->where('parent_directory_id', $file->directory_id)
                    ->where('user_id', $file->user_id),
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

