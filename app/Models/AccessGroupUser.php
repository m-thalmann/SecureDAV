<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessGroupUser extends Model {
    use HasFactory, HasUuids;

    protected $fillable = ['label'];

    protected $hidden = ['password'];

    protected $casts = [
        'password' => 'hashed',
    ];

    public function uniqueIds(): array {
        return ['username'];
    }

    public function accessGroup(): BelongsTo {
        return $this->belongsTo(AccessGroup::class);
    }
}

