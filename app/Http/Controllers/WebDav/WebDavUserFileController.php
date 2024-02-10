<?php

namespace App\Http\Controllers\WebDav;

use App\Http\Controllers\Controller;
use App\Models\Directory;
use App\Models\File;
use App\Models\WebDavUser;
use App\Support\SessionMessage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WebDavUserFileController extends Controller {
    public function __construct() {
        $this->middleware('password.confirm');
    }

    public function create(Request $request, WebDavUser $webDavUser): View {
        $this->authorize('update', $webDavUser);

        $directoryUuid = $request->get('directory', null);

        $directory = $directoryUuid
            ? Directory::where('uuid', $directoryUuid)->firstOrFail()
            : null;

        if ($directory) {
            $this->authorize('view', $directory);
        }

        $directories = Directory::query()
            ->inDirectory($directory)
            ->ordered()
            ->get()
            ->all();

        $files = File::query()
            ->inDirectory($directory)
            ->whereNot->whereHas('webDavUsers', function (Builder $query) use (
                $webDavUser
            ) {
                $query->where('web_dav_user_id', $webDavUser->id);
            })
            ->ordered()
            ->get()
            ->all();

        $breadcrumbs = $directory ? $directory->breadcrumbs : [];

        return view('web-dav-users.files.create', [
            'webDavUser' => $webDavUser,
            'directory' => $directory,
            'directories' => $directories,
            'files' => $files,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    public function store(
        Request $request,
        WebDavUser $webDavUser
    ): RedirectResponse {
        $this->authorize('update', $webDavUser);

        $data = $request->validate([
            'file_uuid' => ['required', 'exists:files,uuid'],
        ]);

        $file = File::where('uuid', $data['file_uuid'])->first();

        $this->authorize('update', $file);

        if (
            $webDavUser
                ->files()
                ->where('file_id', $file->id)
                ->exists()
        ) {
            return redirect()
                ->route('web-dav-users.show', [
                    'web_dav_user' => $webDavUser,
                ])
                ->withFragment('files')
                ->with(
                    'snackbar',
                    SessionMessage::info(
                        __('WebDav user already has access to this file')
                    )->forDuration()
                );
        }

        $webDavUser->files()->attach($file);

        return redirect()
            ->route('web-dav-users.show', [
                'web_dav_user' => $webDavUser,
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
        WebDavUser $webDavUser,
        File $file
    ): RedirectResponse {
        $this->authorize('update', $webDavUser);

        $webDavUser->files()->detach($file);

        return back()->with(
            'snackbar',
            SessionMessage::success(
                __('File access removed successfully')
            )->forDuration()
        );
    }
}
