<?php

namespace App\Services;

use App\Exceptions\EncryptionException;
use App\Exceptions\FileAlreadyExistsException;
use App\Exceptions\FileWriteException;
use App\Exceptions\NoVersionFoundException;
use App\Exceptions\StreamWriteException;
use App\Models\File;
use App\Models\FileVersion;
use App\Support\FileInfo;
use Carbon\Carbon;
use Closure;
use Exception;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileVersionService {
    protected const TMP_SUFFIX = '.tmp';

    public function __construct(
        protected EncryptionService $encryptionService,
        protected FilesystemAdapter $storage
    ) {
    }

    /**
     * Copies the file from the latest version to a new version for the given file.
     *
     * @param \App\Models\File $file
     * @param bool $encrypted Whether the file should be encrypted
     * @param string|null $label The optional label for the new version
     *
     * @throws \App\Exceptions\NoVersionFoundException
     * @throws \App\Exceptions\FileAlreadyExistsException
     * @throws \App\Exceptions\FileWriteException
     * @throws \RuntimeException
     *
     * @return \App\Models\FileVersion
     */
    public function copyLatestVersion(
        File $file,
        bool $encrypted,
        ?string $label = null
    ): FileVersion {
        $version = $file->latestVersion;

        if ($version === null) {
            throw new NoVersionFoundException();
        }

        $encryptionKey = $encrypted ? Str::random(16) : null;

        $storeFileAction = function (
            string $newPath,
            ?string $receivedEncryptionKey
        ) use ($version) {
            $decryptedPath = $this->storage->path($newPath);

            try {
                if ($version->isEncrypted) {
                    if ($receivedEncryptionKey !== null) {
                        $decryptedPath .= static::TMP_SUFFIX;
                    }

                    processResource(fopen($decryptedPath, 'wb'), function (
                        mixed $outputStream
                    ) use ($version) {
                        $this->writeContentsToStream($version, $outputStream);
                    });
                } elseif ($receivedEncryptionKey === null) {
                    if (
                        !$this->storage->copy($version->storage_path, $newPath)
                    ) {
                        throw new FileWriteException();
                    }
                } else {
                    // version is not encrypted; next version should be encrypted
                    $decryptedPath = $this->storage->path(
                        $version->storage_path
                    );
                }

                if ($receivedEncryptionKey !== null) {
                    processResources(
                        [
                            fopen($decryptedPath, 'rb'),
                            fopen($this->storage->path($newPath), 'wb'),
                        ],
                        fn(
                            array $resources
                        ) => $this->encryptionService->encrypt(
                            $receivedEncryptionKey,
                            $resources[0],
                            $resources[1]
                        )
                    );

                    if ($version->isEncrypted) {
                        // delete temporary decrypted file
                        $this->storage->delete($decryptedPath);
                    }
                }
            } catch (Exception $e) {
                if ($receivedEncryptionKey !== null) {
                    @unlink($decryptedPath);
                }

                throw new FileWriteException($e->getMessage(), previous: $e);
            }

            return new FileInfo(
                $this->storage->path($version->storage_path),
                $version->mime_type,
                $version->bytes,
                $version->checksum
            );
        };

        return $this->createVersion(
            $file,
            $storeFileAction,
            $encryptionKey,
            $label
        );
    }

    /**
     * Creates a new version for the given file with the contents of the resource.
     *
     * @param \App\Models\File $file
     * @param resource $resource
     * @param bool $encrypted Whether the file should be encrypted
     * @param string|null $label The optional label for the new version
     *
     * @throws \App\Exceptions\FileAlreadyExistsException
     * @throws \App\Exceptions\FileWriteException
     * @throws \RuntimeException
     *
     * @return \App\Models\FileVersion
     */
    public function createNewVersion(
        File $file,
        mixed $resource,
        bool $encrypted,
        ?string $label = null
    ): FileVersion {
        $versionEncryptionKey = $encrypted ? Str::random(16) : null;

        $storeFileAction = fn(
            string $newPath,
            ?string $encryptionKey
        ) => $this->storeFile(
            $resource,
            $newPath,
            $encryptionKey,
            useTemporaryFile: false
        );

        return $this->createVersion(
            $file,
            $storeFileAction,
            $versionEncryptionKey,
            $label
        );
    }

    /**
     * Creates a new version for the given file and executes the given action to store the file.
     * If the action throws an exception, the transaction is rolled back.
     * **Info:** This function uses a transaction
     *
     * @param \App\Models\File $file
     * @param \Closure $storeFileAction The action to store the file. It receives the new path (inside of the disk) as the first argument and has to return a FileInfo instance of the file.
     * @param string|null $encryptionKey The optional encryption key for the new version
     * @param string|null $label The optional label for the new version
     *
     * @throws \App\Exceptions\FileAlreadyExistsException
     * @throws \RuntimeException
     *
     * @return \App\Models\FileVersion
     */
    protected function createVersion(
        File $file,
        Closure $storeFileAction,
        ?string $encryptionKey,
        ?string $label = null
    ): FileVersion {
        $newPath = Str::uuid()->toString();

        if ($this->storage->exists($newPath)) {
            throw new FileAlreadyExistsException();
        }

        /**
         * @var FileInfo
         */
        $fileInfo = $storeFileAction($newPath, $encryptionKey);

        try {
            DB::beginTransaction();

            $newVersion = $file
                ->versions()
                ->make()
                ->forceFill([
                    'label' => $label,
                    'version' => $file->next_version,
                    'mime_type' => $fileInfo->mimeType,
                    'encryption_key' => $encryptionKey,
                    'storage_path' => $newPath,
                    'checksum' => $fileInfo->checksum,
                    'bytes' => $fileInfo->size,
                    'file_updated_at' => now(),
                ]);

            $newVersion->save();

            $nextVersionSetSuccessfully = $file
                ->forceFill([
                    'next_version' => $file->next_version + 1,
                ])
                ->save();

            if (!$nextVersionSetSuccessfully) {
                throw new RuntimeException('Next version could not be set');
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            $this->storage->delete($newPath);

            throw $e;
        }

        return $newVersion;
    }

    /**
     * Updates the latest version of the given file with the contents of the resource.
     * If the file has an auto version interval and the latest version is older than the interval, a new version is created instead.
     *
     * @param \App\Models\File $file
     * @param resource $resource The resource to read the file from
     *
     * @throws \App\Exceptions\NoVersionFoundException
     * @throws \App\Exceptions\FileWriteException
     *
     * @return bool True if the latest version was updated, false if a new version was created
     */
    public function updateLatestVersion(File $file, mixed $resource): bool {
        $latestVersion = $file->latestVersion;

        if ($latestVersion === null) {
            throw new NoVersionFoundException();
        }

        if ($file->auto_version_hours !== null) {
            $versionCreated = new Carbon($latestVersion->created_at);

            $needsNewVersion = $versionCreated
                ->addRealHours($file->auto_version_hours)
                ->isPast();

            if ($needsNewVersion) {
                $this->createNewVersion(
                    $file,
                    $resource,
                    $latestVersion->isEncrypted
                );

                return false;
            }
        }

        $fileInfo = $this->storeFile(
            $resource,
            $latestVersion->storage_path,
            $latestVersion->encryption_key,
            useTemporaryFile: true
        );

        $latestVersion
            ->forceFill([
                'mime_type' => $fileInfo->mimeType,
                'checksum' => $fileInfo->checksum,
                'bytes' => $fileInfo->size,
                'file_updated_at' => now(),
            ])
            ->save();

        return true;
    }

    /**
     * Stores the given resource to the given path.
     * If an encryption key is set, the file is encrypted before storing it.
     *
     * @param resource $resource
     * @param string $path
     * @param string|null $encryptionKey
     * @param bool $useTemporaryFile Whether the file should be stored to a temporary file and then moved to the correct path
     *
     * @throws \App\Exceptions\FileWriteException
     *
     * @return \App\Support\FileInfo The file info of the stored file
     */
    protected function storeFile(
        mixed $resource,
        string $path,
        ?string $encryptionKey,
        bool $useTemporaryFile
    ): FileInfo {
        $tmpPath = $path;

        if ($useTemporaryFile) {
            $tmpPath .= static::TMP_SUFFIX;
        }

        if (!$encryptionKey) {
            if (!$this->storage->writeStream($tmpPath, $resource)) {
                throw new FileWriteException();
            }
        } else {
            $outputPath = $this->storage->path($tmpPath);

            processResource(fopen($outputPath, 'wb'), function (
                mixed $outputResource
            ) use ($resource, $encryptionKey) {
                try {
                    $this->encryptionService->encrypt(
                        $encryptionKey,
                        $resource,
                        $outputResource
                    );
                } catch (StreamWriteException | EncryptionException $e) {
                    throw new FileWriteException($e->getMessage());
                }
            });
        }

        if ($useTemporaryFile) {
            if (!$this->storage->move($tmpPath, $path)) {
                $this->storage->delete($tmpPath);
                throw new FileWriteException();
            }
        }

        return FileInfo::fromResource($path, $resource);
    }

    /**
     * Writes the contents of the given file version to the given stream.
     *
     * @param \App\Models\FileVersion $version
     * @param resource $outputStream
     *
     * @throws \App\Exceptions\EncryptionException
     * @throws \App\Exceptions\StreamWriteException
     */
    public function writeContentsToStream(
        FileVersion $version,
        mixed $outputStream
    ): void {
        processResource(
            $this->storage->readStream($version->storage_path),
            function (mixed $readStream) use ($version, $outputStream) {
                if ($version->isEncrypted) {
                    $this->encryptionService->decrypt(
                        $version->encryption_key,
                        $readStream,
                        $outputStream
                    );
                } else {
                    stream_copy_to_stream($readStream, $outputStream);
                }
            }
        );
    }

    /**
     * Creates a streamed response to download the given file.
     *
     * @param \App\Models\File $file
     * @param \App\Models\FileVersion $version
     *
     * @throws \InvalidArgumentException If the version does not belong to the file
     * @throws \App\Exceptions\EncryptionException
     * @throws \App\Exceptions\StreamWriteException
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function createDownloadResponse(
        File $file,
        FileVersion $version
    ): StreamedResponse {
        if ($version->file_id !== $file->id) {
            throw new InvalidArgumentException(
                'Version does not belong to file'
            );
        }

        return response()
            ->streamDownload(
                function () use ($version) {
                    processResource(fopen('php://output', 'w'), function (
                        mixed $outputStream
                    ) use ($version) {
                        $this->writeContentsToStream($version, $outputStream);
                    });
                },
                $file->name,
                [
                    'Content-Type' => $version->mime_type,
                    'Content-Length' => $version->bytes,
                ]
            )
            ->setEtag($version->checksum);
    }
}
