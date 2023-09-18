<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class File extends Model {
    use HasFactory;

    protected $fillable = [
        'directory_id',
        'display_name',
        'client_name',
        'description',
        'mime_type',
        'extension',
    ];

    public function scopeForUser(Builder $query, User $user): Builder {
        return $query->where('user_id', $user->id);
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function directory(): BelongsTo {
        return $this->belongsTo(Directory::class);
    }
}

