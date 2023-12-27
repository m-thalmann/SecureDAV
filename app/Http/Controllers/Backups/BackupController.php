<?php

namespace App\Http\Controllers\Backups;

use App\Http\Controllers\Controller;
use App\Models\BackupConfiguration;
use App\Support\SessionMessage;

class BackupController extends Controller {
    public function __invoke(BackupConfiguration $backupConfiguration) {
        $this->authorize('update', $backupConfiguration);

        $success = $backupConfiguration->buildProvider()->backup();

        $message = $success
            ? SessionMessage::success(__('The backup was successful.'))
            : SessionMessage::warning(__('Some files could not be backed up.'));

        return back()->with('snackbar', $message->forDuration());
    }
}

