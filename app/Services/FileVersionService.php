<?php

namespace App\Services;

use App\Exceptions\FileAlreadyExistsException;
use App\Exceptions\FileWriteException;
use App\Exceptions\NoVersionFoundException;
use App\Models\File;
use App\Models\FileVersion;
use Closure;
use Exception;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileVersionService {
    protected const TMP_SUFFIX = '.tmp';

    public function __construct(
        protected FileEncryptionService $fileEncryptionService,
        protected FilesystemAdapter $storage
    ) {
    }

    /**
     * Copies the file from the latest version to a new version for the given file.
     *
     * @param \App\Models\File $file
     * @param string|null $label The optional label for the new version
     *
     * @throws \App\Exceptions\NoVersionFoundException
     * @throws \App\Exceptions\FileAlreadyExistsException
     * @throws \App\Exceptions\FileWriteException
     * @throws \RuntimeException
     */
    public function copyLatestVersion(File $file, ?string $label = null): void {
        $version = $file->latestVersion;

        if ($version === null) {
            throw new NoVersionFoundException();
        }

        $storeFileAction = function (string $newPath) use ($version) {
            $fileCopiedSuccessfully = $this->storage->copy(
                $version->storage_path,
                $newPath
            );

            if (!$fileCopiedSuccessfully) {
                throw new FileWriteException();
            }
        };

        $this->createVersion(
            $file,
            $storeFileAction,
            $version->mime_type,
            $version->checksum,
            $version->bytes,
            $label
        );
    }

    /**
     * Creates a new version for the given file with the uploaded file.
     *
     * @param \App\Models\File $file
     * @param \Illuminate\Http\UploadedFile $uploadedFile
     * @param string|null $label The optional label for the new version
     *
     * @throws \App\Exceptions\FileAlreadyExistsException
     * @throws \App\Exceptions\FileWriteException
     * @throws \RuntimeException
     */
    public function createNewVersion(
        File $file,
        UploadedFile $uploadedFile,
        ?string $label = null
    ) {
        $mimeType = $uploadedFile->getClientMimeType();

        $fileSize = $uploadedFile->getSize();
        $checksum = md5_file($uploadedFile->path());

        $storeFileAction = function (string $newPath) use (
            $file,
            $uploadedFile
        ) {
            $this->storeFile(
                $uploadedFile,
                $newPath,
                $file->encryption_key,
                useTemporaryFile: false
            );
        };

        $this->createVersion(
            $file,
            $storeFileAction,
            $mimeType,
            $checksum,
            $fileSize,
            $label
        );
    }

    /**
     * Creates a new version for the given file and executes the given action to store the file.
     * If the action throws an exception, the transaction is rolled back.
     * **Info:** This function uses a transaction
     *
     * @param \App\Models\File $file
     * @param \Closure $storeFileAction The action to store the file. It receives the new path (inside of the disk) as the first argument.
     * @param string $checksum The checksum of the file
     * @param int $bytes The size of the file in bytes
     * @param string|null $label The optional label for the new version
     *
     * @throws \App\Exceptions\FileAlreadyExistsException
     * @throws \RuntimeException
     */
    protected function createVersion(
        File $file,
        Closure $storeFileAction,
        ?string $mimeType,
        string $checksum,
        int $bytes,
        ?string $label = null
    ): void {
        $newPath = Str::uuid()->toString();

        if ($this->storage->exists($newPath)) {
            throw new FileAlreadyExistsException();
        }

        DB::transaction(function () use (
            $file,
            $storeFileAction,
            $mimeType,
            $label,
            $checksum,
            $bytes,
            $newPath
        ) {
            $newVersion = $file
                ->versions()
                ->make()
                ->forceFill([
                    'label' => $label,
                    'version' => $file->next_version,
                    'mime_type' => $mimeType,
                    'storage_path' => $newPath,
                    'checksum' => $checksum,
                    'bytes' => $bytes,
                ])
                ->save();

            $nextVersionSetSuccessfully = $file
                ->forceFill([
                    'next_version' => $file->next_version + 1,
                ])
                ->save();

            if (!$nextVersionSetSuccessfully) {
                throw new RuntimeException('Next version could not be set');
            }

            $storeFileAction($newPath);
        });
    }

    /**
     * Updates the latest version of the given file with the uploaded file.
     * **Info:** This function uses a transaction
     *
     * @param \App\Models\File $file
     * @param \Illuminate\Http\UploadedFile $uploadedFile
     *
     * @throws \App\Exceptions\NoVersionFoundException
     * @throws \App\Exceptions\FileWriteException
     */
    public function updateLatestVersion(
        File $file,
        UploadedFile $uploadedFile
    ) {
        $mimeType = $uploadedFile->getClientMimeType();

        $fileSize = $uploadedFile->getSize();
        $checksum = md5_file($uploadedFile->path());

        $latestVersion = $file->latestVersion;

        if ($latestVersion === null) {
            throw new NoVersionFoundException();
        }

        DB::transaction(function () use (
            $file,
            $latestVersion,
            $mimeType,
            $fileSize,
            $checksum,
            $uploadedFile
        ) {
            $latestVersion->forceFill([
                'mime_type' => $mimeType,
                'checksum' => $checksum,
                'bytes' => $fileSize,
            ]);

            $latestVersion->save();

            $this->storeFile(
                $uploadedFile,
                $latestVersion->storage_path,
                $file->encryption_key,
                useTemporaryFile: true
            );
        });
    }

    /**
     * Stores the given file at the given path.
     * If an encryption key is set, the file is encrypted before storing it.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $path
     * @param string|null $encryptionKey
     * @param bool $useTemporaryFile Whether the file should be stored to a temporary file and then moved to the correct path (only when using encryption)
     *
     * @throws \App\Exceptions\FileWriteException
     */
    protected function storeFile(
        UploadedFile $file,
        string $path,
        ?string $encryptionKey,
        bool $useTemporaryFile
    ): void {
        if ($encryptionKey) {
            $tmpPath = $path;

            if ($useTemporaryFile) {
                $tmpPath .= static::TMP_SUFFIX;
            }

            $outputPath = $this->storage->path($tmpPath);

            $inputResource = fopen($file->path(), 'rb');
            $outputResource = fopen($outputPath, 'w');

            try {
                $this->fileEncryptionService->encrypt(
                    $encryptionKey,
                    $inputResource,
                    $outputResource
                );
            } catch (Exception $e) {
                fclose($inputResource);
                fclose($outputResource);

                $this->storage->delete($tmpPath);

                throw $e;
            }

            fclose($inputResource);
            fclose($outputResource);

            if ($useTemporaryFile) {
                if (!$this->storage->move($tmpPath, $path)) {
                    $this->storage->delete($tmpPath);
                    throw new FileWriteException();
                }
            }
        } else {
            if (!$this->storage->putFileAs('', $file, $path)) {
                throw new FileWriteException();
            }
        }
    }

    /**
     * Writes the contents of the given file version to the given stream.
     *
     * @param \App\Models\File $file
     * @param \App\Models\FileVersion $version
     * @param resource $outputStream
     *
     * @throws \InvalidArgumentException If the version does not belong to the file
     */
    public function writeContentsToStream(
        File $file,
        FileVersion $version,
        mixed $outputStream
    ): void {
        if ($version->file_id !== $file->id) {
            throw new InvalidArgumentException(
                'Version does not belong to file'
            );
        }

        $readStream = $this->storage->readStream($version->storage_path);

        if ($file->isEncrypted) {
            $this->fileEncryptionService->decrypt(
                $file->encryption_key,
                $readStream,
                $outputStream
            );
        } else {
            stream_copy_to_stream($readStream, $outputStream);
        }

        fclose($readStream);
    }

    /**
     * Creates a streamed response to download the given file.
     *
     * @param \App\Models\File $file
     * @param \App\Models\FileVersion $version
     *
     * @throws \InvalidArgumentException If the version does not belong to the file
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
                function () use ($file, $version) {
                    $outputStream = fopen('php://output', 'w');

                    $this->writeContentsToStream(
                        $file,
                        $version,
                        $outputStream
                    );

                    fclose($outputStream);
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

