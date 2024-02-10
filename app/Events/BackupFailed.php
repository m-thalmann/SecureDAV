<?php

namespace App\Events;

use App\Models\BackupConfiguration;
use Illuminate\Queue\SerializesModels;

class BackupFailed {
    use SerializesModels;

    public function __construct(
        public readonly BackupConfiguration $backupConfiguration,
        public readonly bool $rateLimited = false
    ) {
    }
}
