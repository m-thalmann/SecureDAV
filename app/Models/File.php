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

    protected $fillable = [
        'directory_id',
        'name',
        'description',
        'mime_type',
        'extension',
    ];

    protected $attributes = [
        'next_version' => 1,
    ];

    public function uniqueIds(): array {
        return ['uuid'];
    }

    public function scopeForUser(Builder $query, User $user): Builder {
        return $query->where('user_id', $user->id);
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

    protected function fileName(): Attribute {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                $name = $attributes['name'];

                if ($attributes['extension'] !== null) {
                    $name .= ".{$attributes['extension']}";
                }

                return $name;
            }
        );
    }

    protected function fileIcon(): Attribute {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                return getFileIconForExtension($attributes['extension']);
            }
        );
    }
}

