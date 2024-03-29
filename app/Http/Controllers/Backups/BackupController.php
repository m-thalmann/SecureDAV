<?php

namespace App\Http\Controllers\Backups;

use App\Http\Controllers\Controller;
use App\Jobs\RunBackup;
use App\Models\BackupConfiguration;
use App\Support\SessionMessage;
use Illuminate\Http\RedirectResponse;

class BackupController extends Controller {
    public function __invoke(
        BackupConfiguration $backupConfiguration
    ): RedirectResponse {
        $this->authorize('update', $backupConfiguration);

        if (RunBackup::isRateLimited($backupConfiguration)) {
            $availableIn = RunBackup::getRateLimitedAvailableIn(
                $backupConfiguration
            );

            return back()->with(
                'snackbar',
                SessionMessage::error(
                    __(
                        'You have exceeded the maximum number of allowed backup attempts. Please try again in :seconds seconds.',
                        ['seconds' => $availableIn]
                    )
                )->forDuration()
            );
        }

        RunBackup::dispatch($backupConfiguration);

        return back()->with(
            'snackbar',
            SessionMessage::success(
                __('The backup has been scheduled.')
            )->forDuration()
        );
    }
}
