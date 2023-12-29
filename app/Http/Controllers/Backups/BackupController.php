<?php

namespace App\Http\Controllers\Backups;

use App\Http\Controllers\Controller;
use App\Jobs\RunBackup;
use App\Models\BackupConfiguration;
use App\Support\SessionMessage;

class BackupController extends Controller {
    public function __invoke(BackupConfiguration $backupConfiguration) {
        $this->authorize('update', $backupConfiguration);

        RunBackup::dispatch($backupConfiguration);

        return back()->with(
            'snackbar',
            SessionMessage::success(__('The backup has been scheduled.'))
        );
    }
}

