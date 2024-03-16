<?php

namespace App\Models\Pivots;

use Illuminate\Database\Eloquent\Relations\Pivot;

class BackupConfigurationFile extends Pivot {
    public const PIVOT_COLUMNS = [
        'last_backup_checksum',
        'last_backup_at',
        'last_error',
        'last_error_at',
    ];

    protected $casts = [
        'last_backup_at' => 'datetime',
        'last_error_at' => 'datetime',
    ];
}
