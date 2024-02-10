<?php

namespace App\Http\Controllers\Backups;

use App\Http\Controllers\Controller;
use App\Models\BackupConfiguration;
use App\Models\Directory;
use App\Models\File;
use App\Support\SessionMessage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BackupConfigurationFileController extends Controller {
    public function __construct() {
        $this->middleware('password.confirm');
    }

    public function create(
        Request $request,
        BackupConfiguration $backupConfiguration
    ): View {
        $this->authorize('update', $backupConfiguration);

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
            ->whereNot->whereHas('backupConfigurations', function (
                Builder $query
            ) use ($backupConfiguration) {
                $query->where(
                    'backup_configuration_id',
                    $backupConfiguration->id
                );
            })
            ->ordered()
            ->get()
            ->all();

        $breadcrumbs = $directory ? $directory->breadcrumbs : [];

        return view('backups.files.create', [
            'configuration' => $backupConfiguration,
            'directory' => $directory,
            'directories' => $directories,
            'files' => $files,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    public function store(
        Request $request,
        BackupConfiguration $backupConfiguration
    ): RedirectResponse {
        $this->authorize('update', $backupConfiguration);

        $data = $request->validate([
            'file_uuid' => ['required', 'exists:files,uuid'],
        ]);

        $file = File::where('uuid', $data['file_uuid'])->firstOrFail();

        $this->authorize('update', $file);

        if (
            $backupConfiguration
                ->files()
                ->where('file_id', $file->id)
                ->exists()
        ) {
            return redirect()
                ->route('backups.show', $backupConfiguration)
                ->with(
                    'snackbar',
                    SessionMessage::info(
                        __(
                            'This file is already being backed up by this configuration.'
                        )
                    )->forDuration()
                );
        }

        $backupConfiguration->files()->attach($file);

        return redirect()
            ->route('backups.show', $backupConfiguration)
            ->with(
                'snackbar',
                SessionMessage::success(
                    __('File successfully added to backup')
                )->forDuration()
            );
    }

    public function destroy(
        BackupConfiguration $backupConfiguration,
        File $file
    ): RedirectResponse {
        $this->authorize('update', $backupConfiguration);

        $backupConfiguration->files()->detach($file);

        return back()->with(
            'snackbar',
            SessionMessage::success(
                __('File successfully removed from backup')
            )->forDuration()
        );
    }
}
