<?php

namespace App\Backups;

use App\Models\BackupConfiguration;
use App\Models\File;
use App\Services\FileVersionService;
use Exception;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class WebDavBackupProvider extends AbstractBackupProvider {
    public function __construct(
        BackupConfiguration $backupConfiguration,
        FileVersionService $fileVersionService,
        protected readonly HttpClient $httpClient
    ) {
        parent::__construct($backupConfiguration, $fileVersionService);
    }

    public static function getDisplayInformation(): array {
        return [
            'name' => __('WebDAV'),
            'icon' => 'fa-solid fa-hard-drive',
            'description' => __(
                'Uploads the latest version of the files to a WebDAV server. This can also be used to backup to Nextcloud for example.'
            ),
        ];
    }

    public static function getConfigFormTemplate(): ?string {
        return 'backups.partials.providers.webdav';
    }

    public static function validateConfig(array $config): array {
        return Validator::make($config, [
            'method' => ['required', 'string', 'in:PUT,POST'],
            'targetUrl' => ['required', 'url'],
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ])->validate();
    }

    protected function backupFile(File $file): void {
        $response = processResource(
            $this->getFileContentStream($file),
            function (mixed $body) use ($file) {
                $config = $this->getWebDavConfig();

                $targetUrl = $config['targetUrl'] . $file->name;

                return $this->httpClient->request(
                    $config['method'],
                    $targetUrl,
                    [
                        'auth' => [$config['username'], $config['password']],
                        'body' => $body,
                    ]
                );
            }
        );

        if (
            $response->getStatusCode() < 200 ||
            $response->getStatusCode() >= 300
        ) {
            throw new Exception(
                __('Error') . ': ' . $response->getReasonPhrase()
            );
        }
    }

    protected function getWebDavConfig(): array {
        $method = Arr::get(
            $this->backupConfiguration->config,
            'method',
            default: 'PUT'
        );
        $targetUrl = Arr::get($this->backupConfiguration->config, 'targetUrl');
        $username = Arr::get($this->backupConfiguration->config, 'username');
        $password = Arr::get($this->backupConfiguration->config, 'password');

        if ($targetUrl === null) {
            throw new Exception(
                __('The target url for the backup is not configured.')
            );
        }

        if ($username === null || $password === null) {
            throw new Exception(
                __('The credentials for the backup are not configured.')
            );
        }

        return [
            'method' => $method,
            'targetUrl' => str($targetUrl)->finish('/'),
            'username' => $username,
            'password' => $password,
        ];
    }
}
