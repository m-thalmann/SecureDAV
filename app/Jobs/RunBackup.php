<?php

namespace App\Jobs;

use App\Events\BackupFailed;
use App\Models\BackupConfiguration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Throwable;

class RunBackup implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public BackupConfiguration $backupConfiguration;

    public function __construct(BackupConfiguration $backupConfiguration) {
        $this->backupConfiguration = $backupConfiguration->withoutRelations();
    }

    public function handle(): void {
        $success = false;

        try {
            $success = $this->backupConfiguration->buildProvider()->backup();
        } catch (Throwable $exception) {
        }

        if (!$success) {
            event(new BackupFailed($this->backupConfiguration));
        }
    }

    public function middleware(): array {
        return [new RateLimited('backups')];
    }
}

