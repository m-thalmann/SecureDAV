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

class File extends Model {
    use HasFactory, HasUuids, SoftDeletes;

    protected $hidden = ['next_version'];

    protected $fillable = ['directory_id', 'name', 'description', 'mime_type'];

    protected $attributes = [
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

    protected function fileIcon(): Attribute {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                return getFileIconForExtension($this->extension);
            }
        );
    }
}

