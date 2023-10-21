<?php

namespace App\Http\Controllers;

use App\Models\Directory;
use App\Support\SessionMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DirectoryController extends Controller {
    public function __construct() {
        $this->authorizeResource(Directory::class);
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
                'max:128',
                Rule::unique('directories', 'name')
                    ->where('parent_directory_id', $parentDirectory?->id)
                    ->where('user_id', authUser()->id),
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
        // TODO: make parent_directory_id editable
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:128',
                Rule::unique('directories', 'name')
                    ->where(
                        'parent_directory_id',
                        $directory->parent_directory_id
                    )
                    ->where('user_id', $directory->user_id)
                    ->ignore($directory),
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

