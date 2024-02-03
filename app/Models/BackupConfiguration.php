<?php

namespace App\Models;

use App\Backups\AbstractBackupProvider;
use App\Casts\EncryptedBackupConfig;
use App\Models\Pivots\BackupConfigurationFile;
use App\Support\BackupSchedule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Arr;

class BackupConfiguration extends Model {
    use HasFactory, HasUuids;

    protected $dateFormat = 'c';

    protected $fillable = ['label', 'config', 'cron_schedule', 'active'];

    protected $casts = [
        'config' => EncryptedBackupConfig::class,
        'store_with_version' => 'boolean',
        'started_at' => 'datetime',
        'last_run_at' => 'datetime',
        'active' => 'boolean',
    ];

    protected $attributes = [
        'config' => null,
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

    protected function schedule(): Attribute {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                if ($attributes['cron_schedule'] === null) {
                    return null;
                }

                return new BackupSchedule($attributes['cron_schedule']);
            }
        );
    }

    protected function maskedConfig(): Attribute {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                $config = $this->config;

                foreach (
                    $this->provider_class::getSensitiveConfigKeys()
                    as $sensitiveKey
                ) {
                    if (Arr::has($config, $sensitiveKey)) {
                        Arr::set($config, $sensitiveKey, '********');
                    }
                }

                return $config;
            }
        );
    }

    public function buildProvider(): AbstractBackupProvider {
        return app()->makeWith($this->provider_class, [
            'backupConfiguration' => $this,
        ]);
    }
}

