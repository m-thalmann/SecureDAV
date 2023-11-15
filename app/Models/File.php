<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class File extends Model {
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * Possible options for the auto version hours.
     *
     * @var float[]
     */
    const AUTO_VERSION_HOURS = [
        0.5, // 30 min
        1, // 1 hour
        2, // 2 hours
        12, // 12 hours
        24, // 1 day
        48, // 2 days
        168, // 1 week
        720, // 30 days
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

    public function accessGroups(): BelongsToMany {
        return $this->belongsToMany(AccessGroup::class, 'access_group_files');
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

    protected function isEncrypted(): Attribute {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                return $attributes['encryption_key'] !== null;
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
}

