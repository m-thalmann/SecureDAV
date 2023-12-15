<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class WebDavUser extends Model {
    use HasFactory, HasUuids;

    protected $fillable = ['label', 'active', 'readonly'];

    protected $hidden = ['password'];

    protected $casts = [
        'password' => 'hashed',
        'active' => 'boolean',
        'readonly' => 'boolean',
        'last_access' => 'datetime',
    ];

    public function uniqueIds(): array {
        return ['username'];
    }

    public function scopeForUser(Builder $query, User $user): Builder {
        return $query->where('user_id', $user->id);
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function files(): BelongsToMany {
        return $this->belongsToMany(
            File::class,
            'web_dav_user_files'
        )->ordered();
    }
}

