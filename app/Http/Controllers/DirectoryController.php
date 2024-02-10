<?php

namespace App\Http\Controllers;

use App\Models\Directory;
use App\Rules\NotStringContains;
use App\Rules\UniqueFileName;
use App\Support\SessionMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DirectoryController extends Controller {
    public function __construct() {
        $this->authorizeResource(Directory::class);

        $this->middleware('password.confirm')->only([
            'edit',
            'update',
            'destroy',
        ]);
    }

    public function create(Request $request): View {
        $parentDirectoryUuid = $request->get('directory', null);
        $parentDirectory = $parentDirectoryUuid
            ? Directory::where('uuid', $parentDirectoryUuid)->firstOrFail()
            : null;

        if ($parentDirectory) {
            $this->authorize('update', $parentDirectory);
        }

        return view('directories.create', [
            'parentDirectory' => $parentDirectory,
        ]);
    }

    public function store(Request $request): RedirectResponse {
        $parentDirectoryUuid = $request->get('parent_directory_uuid', null);
        $parentDirectory = $parentDirectoryUuid
            ? Directory::where('uuid', $parentDirectoryUuid)->firstOrFail()
            : null;

        if ($parentDirectory) {
            $this->authorize('update', $parentDirectory);
        }

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:' . config('core.files.max_name_length'),
                new NotStringContains(config('core.files.illegal_characters')),
                new UniqueFileName(
                    authUser()->id,
                    inDirectoryId: $parentDirectory?->id
                ),
            ],
        ]);

        $directory = authUser()
            ->directories()
            ->create([
                'parent_directory_id' => $parentDirectory?->id,
                'name' => $data['name'],
            ]);

        return redirect()
            ->route('browse.index', $directory->uuid)
            ->with(
                'snackbar',
                SessionMessage::success(
                    __('Directory created successfully')
                )->forDuration()
            );
    }

    public function edit(Directory $directory): View {
        return view('directories.edit', [
            'directory' => $directory,
        ]);
    }

    public function update(
        Request $request,
        Directory $directory
    ): RedirectResponse {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:' . config('core.files.max_name_length'),
                new NotStringContains(config('core.files.illegal_characters')),
                new UniqueFileName(
                    $directory->user_id,
                    inDirectoryId: $directory->parent_directory_id,
                    ignoreDirectory: $directory
                ),
            ],
        ]);

        $directory->update($data);

        return redirect()
            ->route('browse.index', $directory->uuid)
            ->with(
                'snackbar',
                SessionMessage::success(
                    __('Directory updated successfully')
                )->forDuration()
            );
    }

    public function destroy(Directory $directory): RedirectResponse {
        if (!$directory->isEmpty) {
            return back()->with(
                'snackbar',
                SessionMessage::error(
                    __('Directory is not empty')
                )->forDuration()
            );
        }

        $parentDirectory = $directory->parentDirectory;

        $directory->delete();

        return redirect()
            ->route('browse.index', $parentDirectory?->uuid)
            ->with(
                'snackbar',
                SessionMessage::success(
                    __('Directory deleted permanently')
                )->forDuration()
            );
    }
}
