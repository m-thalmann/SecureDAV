<?php

namespace App\Backups;

use App\Models\File;
use Illuminate\Support\Facades\Log;

class LogBackupProvider extends AbstractBackupProvider {
    public static function getDisplayInformation(): array {
        return [
            'name' => __('Log'),
            'icon' => 'fa-solid fa-file-lines',
            'description' => __(
                'Logs the backup for each file for testing purposes.'
            ),
        ];
    }

    public static function getConfigFormTemplate(): ?string {
        return null;
    }

    public static function validateConfig(array $config): array {
        return [];
    }

    public static function getSensitiveConfigKeys(): array {
        return [];
    }

    protected function backupFile(File $file, string $targetName): void {
        Log::info(
            "Backup \"{$this->backupConfiguration->label}\" ({$this->backupConfiguration->id}) " .
                "running for file \"{$file->name}\" ($file->id) " .
                "with version {$file->latestVersion->version} " .
                "to target \"{$targetName}\""
        );
    }
}
