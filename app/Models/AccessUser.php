<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AccessUser extends Model {
    use HasFactory;

    protected $fillable = ['label', 'password', 'active', 'readonly'];

    protected $hidden = ['password'];

    protected $casts = [
        'password' => 'hashed',
        'last_access' => 'datetime',
    ];

    public function scopeForUser(Builder $query, User $user): Builder {
        return $query->where('user_id', $user->id);
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function files(): BelongsToMany {
        return $this->belongsToMany(File::class, 'access_user_files');
    }
}

