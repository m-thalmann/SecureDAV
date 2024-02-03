<?php

namespace App\Models;

use App\Models\Pivots\BackupConfigurationFile;
use App\Rules\UniqueFileName;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class File extends Model {
    use HasFactory, HasUuids, SoftDeletes, Prunable;

    protected $dateFormat = 'c';

    /**
     * Possible options for the auto version hours.
     *
     * **Important:** Numbers must be floats
     *
     * @var float[]
     */
    const AUTO_VERSION_HOURS = [
        0.5, // 30 min
        1.0, // 1 hour
        2.0, // 2 hours
        12.0, // 12 hours
        24.0, // 1 day
        48.0, // 2 days
        168.0, // 1 week
        720.0, // 30 days
    ];

    protected $hidden = ['next_version'];

    protected $fillable = [
        'directory_id',
        'name',
        'description',
        'auto_version_hours',
    ];

    protected $attributes = [
        'auto_version_hours' => null,
        'next_version' => 1,
    ];

    public function uniqueIds(): array {
        return ['uuid'];
    }

    public function scopeForUser(Builder $query, User $user): Builder {
        return $query->where('user_id', $user->id);
    }

    public function scopeInDirectory(
        Builder $query,
        ?Directory $directory,
        bool $filterUser = true
    ): Builder {
        if ($directory === null) {
            return $query
                ->whereNull('directory_id')
                ->when(
                    $filterUser,
                    fn(Builder $query) => $query->forUser(authUser())
                );
        }

        return $query->where('directory_id', $directory->id);
    }

    public function scopeOrdered(
        Builder $query,
        string $direction = 'asc'
    ): Builder {
        return $query->orderBy('name', $direction);
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function webDavUsers(): BelongsToMany {
        return $this->belongsToMany(WebDavUser::class, 'web_dav_user_files');
    }

    public function backupConfigurations(): BelongsToMany {
        return $this->belongsToMany(
            BackupConfiguration::class,
            'backup_configuration_files'
        )
            ->using(BackupConfigurationFile::class)
            ->withPivot(BackupConfigurationFile::PIVOT_COLUMNS);
    }

    public function directory(): BelongsTo {
        return $this->belongsTo(Directory::class);
    }

    public function versions(): HasMany {
        return $this->hasMany(FileVersion::class)->orderBy('version', 'desc');
    }

    public function latestVersion(): HasOne {
        return $this->hasOne(FileVersion::class)->latestVersion();
    }

    protected function isLatestVersionEncrypted(): Attribute {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                return $this->latestVersion?->isEncrypted;
            }
        );
    }

    protected function extension(): Attribute {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                $lastDot = strrpos($attributes['name'], '.');

                if (
                    $lastDot === false ||
                    $lastDot === 0 ||
                    $lastDot === strlen($attributes['name']) - 1
                ) {
                    return null;
                }

                return substr($attributes['name'], $lastDot + 1);
            }
        );
    }

    protected function fileLastUpdatedAt(): Attribute {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                return $this->latestVersion?->file_updated_at;
            }
        );
    }

    protected function fileIcon(): Attribute {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                $icon = getFileIconForExtension($this->extension);

                if (Str::startsWith($attributes['name'], '.')) {
                    $icon .= ' opacity-20';
                }

                return $icon;
            }
        );
    }

    protected function webdavUrl(): Attribute {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                return route('webdav.files', [
                    $attributes['uuid'],
                    $attributes['name'],
                ]);
            }
        );
    }

    /**
     * Moves the file to the given directory.
     * Does **not** save the changes automatically.
     *
     * @param Directory|null $directory The directory to move the file to.
     *
     * @throws \Illuminate\Validation\ValidationException If the file name already exists in the directory.
     */
    public function move(?Directory $directory): void {
        $validator = Validator::make(
            [
                'name' => $this->name,
            ],
            [
                'name' => [
                    new UniqueFileName(
                        $this->user_id,
                        inDirectoryId: $directory?->id,
                        ignoreFile: $this
                    ),
                ],
            ]
        );

        $validator->validate();

        $this->directory_id = $directory?->id;
    }

    public function prunable(): Builder {
        return static::query()->where(
            'deleted_at',
            '<=',
            now()
                ->subDays(config('core.files.trash.auto_delete_days'))
                ->toIso8601String()
        );
    }

    protected static function booted(): void {
        static::forceDeleting(function (File $file) {
            foreach ($file->versions as $version) {
                $version->delete();
            }
        });
    }
}
