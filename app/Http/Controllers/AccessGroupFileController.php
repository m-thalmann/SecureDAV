<?php

namespace App\Http\Controllers;

use App\Models\AccessGroup;
use App\Models\Directory;
use App\Models\File;
use App\Support\SessionMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccessGroupFileController extends Controller {
    public function create(Request $request, AccessGroup $accessGroup): View {
        $this->authorize('update', $accessGroup);

        $directoryUuid = $request->get('directory', null);

        $directory = $directoryUuid
            ? Directory::where('uuid', $directoryUuid)->firstOrFail()
            : null;

        if ($directory) {
            $this->authorize('update', $directory);
        }

        $directoriesQuery = null;
        $filesQuery = null;

        $breadcrumbs = [];

        if ($directory) {
            $directoriesQuery = $directory->directories();
            $filesQuery = $directory->files();

            $breadcrumbs = $directory->breadcrumbs;
        } else {
            $directoriesQuery = Directory::query()
                ->whereNull('parent_directory_id')
                ->forUser(auth()->user());
            $filesQuery = File::query()
                ->whereNull('directory_id')
                ->forUser(auth()->user());
        }

        $directories = $directoriesQuery
            ->orderBy('name', 'asc')
            ->get()
            ->all();

        $files = $filesQuery
            ->orderBy('name', 'asc')
            ->orderBy('extension', 'asc')
            ->get()
            ->all();

        return view('access-groups.files.create', [
            'accessGroup' => $accessGroup,
            'directory' => $directory,
            'directories' => $directories,
            'files' => $files,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    public function store(
        Request $request,
        AccessGroup $accessGroup
    ): RedirectResponse {
        $this->authorize('update', $accessGroup);

        $data = $request->validate([
            'file_uuid' => ['required', 'exists:files,uuid'],
        ]);

        $file = File::where('uuid', $data['file_uuid'])->first();

        $this->authorize('update', $file);

        if (
            $accessGroup
                ->files()
                ->where('file_id', $file->id)
                ->exists()
        ) {
            return redirect()
                ->route('access-groups.show', [
                    'access_group' => $accessGroup,
                ])
                ->withFragment('files')
                ->with(
                    'snackbar',
                    SessionMessage::warning(
                        __('Group has already access to this file')
                    )->forDuration()
                );
        }

        $accessGroup->files()->attach($file);

        return redirect()
            ->route('access-groups.show', [
                'access_group' => $accessGroup,
            ])
            ->withFragment('files')
            ->with(
                'snackbar',
                SessionMessage::success(
                    __('File access added successfully')
                )->forDuration()
            );
    }

    public function destroy(
        AccessGroup $accessGroup,
        File $file
    ): RedirectResponse {
        $this->authorize('update', $accessGroup);

        $accessGroup->files()->detach($file);

        return redirect()
            ->route('access-groups.show', [
                'access_group' => $accessGroup,
            ])
            ->withFragment('files')
            ->with(
                'snackbar',
                SessionMessage::success(
                    __('File access removed successfully')
                )->forDuration()
            );
    }
}

