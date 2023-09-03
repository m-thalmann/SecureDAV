<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class User extends Authenticatable implements MustVerifyEmail {
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['name', 'email', 'password'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = ['password', 'remember_token'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected function initials(): Attribute {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                $names = explode(' ', $attributes['name']);
                $initials = '';

                if (count($names) >= 2) {
                    $first = Arr::first($names)[0];
                    $last = Arr::last($names)[0];

                    $initials = $first . $last;
                } else {
                    $initials = Str::substr($attributes['name'], 0, 2);
                }

                return Str::upper($initials);
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
