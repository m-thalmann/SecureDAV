<?php

namespace App\Jobs;

use App\Events\BackupFailed;
use App\Models\BackupConfiguration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class RunBackup implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected const RATE_LIMITER_KEY = 'backup';
    public const RATE_LIMITER_ATTEMPTS = 1;

    public BackupConfiguration $backupConfiguration;

    public function __construct(BackupConfiguration $backupConfiguration) {
        $this->backupConfiguration = $backupConfiguration->withoutRelations();
    }

    public function handle(): void {
        if (static::isRateLimited($this->backupConfiguration)) {
            event(
                new BackupFailed($this->backupConfiguration, rateLimited: true)
            );

            return;
        }

        $rateLimiterKey = static::getRateLimiterKey($this->backupConfiguration);

        RateLimiter::hit($rateLimiterKey);

        $success = false;

        try {
            $success = $this->backupConfiguration->buildProvider()->backup();
        } catch (Throwable $exception) {
        }

        if (!$success) {
            event(new BackupFailed($this->backupConfiguration));
        }
    }

    public static function isRateLimited(
        BackupConfiguration $backupConfiguration
    ): bool {
        $rateLimiterKey = static::getRateLimiterKey($backupConfiguration);

        return RateLimiter::tooManyAttempts(
            $rateLimiterKey,
            static::RATE_LIMITER_ATTEMPTS
        );
    }

    public static function getRateLimitedAvailableIn(
        BackupConfiguration $backupConfiguration
    ): int {
        $rateLimiterKey = static::getRateLimiterKey($backupConfiguration);

        return RateLimiter::availableIn($rateLimiterKey);
    }

    public static function getRateLimiterKey(
        BackupConfiguration $backupConfiguration
    ): string {
        return static::RATE_LIMITER_KEY . ':' . $backupConfiguration->id;
    }
}

