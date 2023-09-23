<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class File extends Model {
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'directory_id',
        'name',
        'description',
        'mime_type',
        'extension',
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

    public function directory(): BelongsTo {
        return $this->belongsTo(Directory::class);
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
}

