<?php

namespace App\Models;

use App\Backups\AbstractBackupProvider;
use App\Models\Pivots\BackupConfigurationFile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BackupConfiguration extends Model {
    use HasFactory, HasUuids;

    protected $fillable = ['label', 'config', 'cron_schedule'];

    protected $casts = [
        'config' => 'json',
        'started_at' => 'datetime',
        'last_run_at' => 'datetime',
    ];

    protected $attributes = [
        'config' => '[]',
    ];

    public function uniqueIds(): array {
        return ['uuid'];
    }

    public function scopeWithSchedule(Builder $query): Builder {
        return $query->whereNot('cron_schedule', null);
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function files(): BelongsToMany {
        return $this->belongsToMany(File::class, 'backup_configuration_files')
            ->using(BackupConfigurationFile::class)
            ->withPivot(BackupConfigurationFile::PIVOT_COLUMNS);
    }

    public function buildProvider(): AbstractBackupProvider {
        return app()->makeWith($this->provider_class, [
            'backupConfiguration' => $this,
        ]);
    }
}

