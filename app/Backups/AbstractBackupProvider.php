<?php

namespace App\Backups;

use App\Models\BackupConfiguration;
use App\Models\File;
use App\Models\User;
use App\Services\FileVersionService;
use Exception;
use Illuminate\Support\Str;

abstract class AbstractBackupProvider {
    protected const STORE_WITH_VERSION_AMOUNT_DIGITS = 3;

    public function __construct(
        protected readonly BackupConfiguration $backupConfiguration,
        protected readonly FileVersionService $fileVersionService
    ) {
        $this->backupConfiguration->load(['files.latestVersion']);
    }

    /**
     * Returns the display information for the provider.
     *
     * @return array Associative array with the following keys:
     *               - name: The name of the provider (translated)
     *               - icon: The font awesome icon of the provider (optional if iconUrl is set)
     *               - iconUrl: The URL of the icon of the provider (optional if icon is set)
     *               - description: The description of the provider (translated)
     */
    abstract public static function getDisplayInformation(): array;

    /**
     * Returns the config-form template for the provider or null if no template is needed.
     * This template will be rendered in the backup configuration form (creating and editing).
     *
     * @return string|null
     */
    abstract public static function getConfigFormTemplate(): string|null;

    /**
     * Validates the config values for the provider and returns them as an array.
     *
     * @throws \Illuminate\Validation\ValidationException If the config values are invalid.
     *
     * @param array $config The config values to validate.
     */
    abstract public static function validateConfig(array $config): array;

    /**
     * Returns the config keys that should not be displayed to the client in clear text (only masked).
     * Examples would be credentials, tokens, etc.
     *
     * **Note:** The config keys may also use the dot-notation for nested arrays.
     *
     * @return array
     */
    abstract public static function getSensitiveConfigKeys(): array;

    /**
     * Uploads the latest version of the file to the provider. Stores the file with the given name.
     * If the backup did not succeed an exception should be thrown.
     *
     * @param \App\Models\File $file
     * @param string $targetName The target file name to store
     *
     * @throws \Exception If the backup did not succeed. Will be stored in the database.
     */
    abstract protected function backupFile(
        File $file,
        string $targetName
    ): void;

    /**
     * Uploads the latest version of all files to the provider.
     *
     * @return bool True if all files were uploaded successfully, false otherwise.
     */
    public function backup(): bool {
        $success = true;

        $this->backupConfiguration
            ->forceFill([
                'started_at' => now(),
            ])
            ->save();

        foreach ($this->backupConfiguration->files as $file) {
            $latestVersion = $file->latestVersion;

            if (
                $latestVersion === null ||
                $latestVersion->checksum === $file->pivot->last_backup_checksum
            ) {
                continue;
            }

            $targetName = $file->name;

            if ($this->backupConfiguration->store_with_version) {
                $paddedVersion = Str::padLeft(
                    $file->latestVersion->version,
                    static::STORE_WITH_VERSION_AMOUNT_DIGITS,
                    '0'
                );

                $targetName = "v{$paddedVersion}-{$targetName}";
            }

            try {
                $this->backupFile($file, $targetName);
            } catch (Exception $e) {
                $this->backupConfiguration
                    ->files()
                    ->updateExistingPivot($file->id, [
                        'last_error' => $e->getMessage(),
                        'last_error_at' => now(),
                    ]);

                $success = false;

                continue;
            }

            $this->backupConfiguration
                ->files()
                ->updateExistingPivot($file->id, [
                    'last_backup_checksum' => $file->latestVersion->checksum,
                    'last_backup_at' => now(),
                    'last_error' => null,
                    'last_error_at' => null,
                ]);
        }

        $this->backupConfiguration
            ->forceFill([
                'started_at' => null,
                'last_run_at' => now(),
            ])
            ->save();

        return $success;
    }

    /**
     * Returns a stream containing the contents of the given file.
     *
     * @param \App\Models\File $file
     *
     * @return resource
     */
    protected function getFileContentStream(File $file): mixed {
        $body = fopen('php://memory', 'rb+');

        $this->fileVersionService->writeContentsToStream(
            $file,
            $file->latestVersion,
            $body
        );

        rewind($body);

        return $body;
    }

    /**
     * Returns the value of the given config key for the provider.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed The value of the config key.
     */
    protected static function getConfig(
        string $key,
        mixed $default = null
    ): mixed {
        $className = static::class;

        return config("backups.providers.{$className}.{$key}", $default);
    }

    /**
     * Creates a new backup configuration for the given user.
     *
     * @param \App\Models\User $user
     * @param array $config
     * @param string $label
     *
     * @return BackupConfiguration
     */
    public static function createConfiguration(
        User $user,
        array $config,
        string $label,
        bool $storeWithVersion
    ): BackupConfiguration {
        $backupConfiguration = $user
            ->backupConfigurations()
            ->make()
            ->forceFill([
                'provider_class' => static::class,
                'label' => $label,
                'store_with_version' => $storeWithVersion,
                'config' => $config,
                'active' => true,
            ]);

        $backupConfiguration->save();

        return $backupConfiguration;
    }
}

