<?php

namespace App\Models;

use App\Notifications\VerifyEmailNotification;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable implements MustVerifyEmail {
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    protected $fillable = ['name', 'email', 'password', 'timezone'];

    protected $hidden = ['password', 'remember_token', 'encryption_key'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_admin' => 'boolean',
        'is_webdav_suspended' => 'boolean',
        'encryption_key' => 'encrypted',
    ];

    protected $attributes = [
        'is_admin' => false,
        'is_webdav_suspended' => false,
    ];

    public function files(): HasMany {
        return $this->hasMany(File::class)->ordered();
    }

    public function directories(): HasMany {
        return $this->hasMany(Directory::class)->ordered();
    }

    public function webDavUsers(): HasMany {
        return $this->hasMany(WebDavUser::class);
    }

    public function backupConfigurations(): HasMany {
        return $this->hasMany(BackupConfiguration::class);
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
            $this->notify(new VerifyEmailNotification());
        }
    }

    /**
     * Format the given date to the user's timezone
     *
     * @param \Carbon\CarbonInterface|null $date
     *
     * @return string
     */
    public function formatDate(?CarbonInterface $date): ?string {
        if ($date === null) {
            return null;
        }

        $outputDate = $date->clone();
        $outputDate->setTimezone(
            $this->timezone ?? config('app.default_timezone')
        );

        return $outputDate->toDateTimeString();
    }

    protected static function booted(): void {
        static::deleting(function (User $user) {
            foreach ($user->files as $file) {
                $file->forceDelete();
            }
        });
    }
}
