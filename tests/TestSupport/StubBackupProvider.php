<?php

namespace Tests\TestSupport;

use App\Backups\AbstractBackupProvider;
use App\Models\File;
use Illuminate\Support\Facades\Validator;

class StubBackupProvider extends AbstractBackupProvider {
    public static ?array $customConfigValidator = null;
    public static array $customSensitiveConfigKeys = [];

    public static function getDisplayInformation(): array {
        return [
            'name' => 'Stub Backup',
            'icon' => 'fa-solid fa-hard-drive',
            'description' => 'A stub backup provider for testing purposes.',
        ];
    }

    public static function getConfigFormTemplate(): ?string {
        return null;
    }

    public static function validateConfig(array $config): array {
        if (!static::$customConfigValidator) {
            return [];
        }

        return Validator::make(
            $config,
            static::$customConfigValidator
        )->validate();
    }

    public static function getSensitiveConfigKeys(): array {
        return static::$customSensitiveConfigKeys;
    }

    protected function backupFile(File $file): void {
    }
}

