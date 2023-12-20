<?php

namespace App\Backups;

use App\Models\BackupConfiguration;
use App\Models\File;
use App\Models\User;
use App\Services\FileVersionService;
use Exception;

abstract class AbstractBackupProvider {
    public function __construct(
        protected readonly BackupConfiguration $backupConfiguration,
        protected readonly FileVersionService $fileVersionService
    ) {
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
     * Uploads the latest version of the file to the provider.
     * If the backup did not succeed an exception should be thrown.
     *
     * @param \App\Models\File $file
     *
     * @throws \Exception If the backup did not succeed. Will be stored in the database.
     */
    abstract protected function backupFile(File $file): void;

    /**
     * Uploads the latest version of all files to the provider.
     *
     * @return bool True if all files were uploaded successfully, false otherwise.
     */
    public function backup(): bool {
        $success = true;

        foreach ($this->backupConfiguration->files as $file) {
            $latestVersion = $file->latestVersion;

            if (
                $latestVersion === null ||
                $latestVersion->checksum === $file->pivot->last_backup_checksum
            ) {
                continue;
            }

            try {
                $this->backupFile($file);
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

            $this->backupConfiguration
                ->forceFill([
                    'last_run_at' => now(),
                ])
                ->save();
        }

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
     * @param string|null $label
     *
     * @return BackupConfiguration
     */
    public static function createConfiguration(
        User $user,
        array $config,
        ?string $label = null
    ): BackupConfiguration {
        $backupConfiguration = $user
            ->backupConfigurations()
            ->make([
                'label' => $label,
                'config' => $config,
            ])
            ->forceFill([
                'provider_class' => static::class,
            ]);

        $backupConfiguration->save();

        return $backupConfiguration;
    }
}

