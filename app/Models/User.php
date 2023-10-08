<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable implements MustVerifyEmail {
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected $attributes = [
        'is_admin' => false,
    ];

    public function files(): HasMany {
        return $this->hasMany(File::class);
    }

    public function directories(): HasMany {
        return $this->hasMany(Directory::class);
    }

    protected function initials(): Attribute {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                return generateInitials($attributes['name']);
            }
        );
    }

    public function hasVerifiedEmail(): bool {
        return !config('app.email_verification_enabled') ||
            parent::hasVerifiedEmail();
    }

    public function sendEmailVerificationNotification(): void {
        if (config('app.email_verification_enabled')) {
            parent::sendEmailVerificationNotification();
        }
    }
}

