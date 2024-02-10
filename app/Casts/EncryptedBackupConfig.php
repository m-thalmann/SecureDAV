<?php

namespace App\Casts;

use App\Models\BackupConfiguration;
use App\Services\EncryptionService;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class EncryptedBackupConfig implements CastsAttributes {
    protected readonly EncryptionService $encryptionService;

    public function __construct() {
        $this->encryptionService = app(EncryptionService::class);
    }

    public function get(
        Model $model,
        string $key,
        mixed $value,
        array $attributes
    ): mixed {
        $this->validateModel($model);

        if ($value === null) {
            return null;
        }

        $encryptionKey = $model->user->encryption_key;

        $encryptedValue = base64_decode($value);

        $input = createStream($encryptedValue);
        $output = createStream();

        return processResources([$input, $output], function (
            array $resources
        ) use ($encryptionKey) {
            $input = $resources[0];
            $output = $resources[1];

            $this->encryptionService->decrypt($encryptionKey, $input, $output);

            rewind($output);

            $decryptedValue = stream_get_contents($output);

            return json_decode($decryptedValue, true);
        });
    }

    public function set(
        Model $model,
        string $key,
        mixed $value,
        array $attributes
    ): mixed {
        $this->validateModel($model);

        if ($value === null) {
            return null;
        }

        $encryptionKey = $model->user->encryption_key;

        $input = createStream(json_encode($value));
        $output = createStream();

        return processResources([$input, $output], function (
            array $resources
        ) use ($encryptionKey) {
            $input = $resources[0];
            $output = $resources[1];

            $this->encryptionService->encrypt($encryptionKey, $input, $output);

            rewind($output);

            $encryptedValue = stream_get_contents($output);

            return base64_encode($encryptedValue);
        });
    }

    protected function validateModel(Model $model): void {
        if (!$model instanceof BackupConfiguration) {
            throw new InvalidArgumentException(
                static::class .
                    ' can only be used on ' .
                    BackupConfiguration::class
            );
        }
    }
}
