<?php

namespace App\WebDav\Filesystem;

use App\Models\File;
use App\Models\FileVersion;
use App\Services\FileVersionService;
use App\WebDav\AuthBackend;
use Carbon\CarbonInterface;
use Sabre\DAV;

/**
 * Virtual file which contains the contents of the given file.
 * The contents will be written to a new in-memory stream when fetched.
 */
class VirtualFile extends DAV\File {
    protected FileVersion $fileVersion;

    function __construct(
        protected AuthBackend $authBackend,
        protected FileVersionService $fileVersionService,
        public readonly File $file
    ) {
        $this->fileVersion = $file->latestVersion;
    }

    function getName(): string {
        return $this->file->name;
    }

    function get(): mixed {
        $stream = fopen('php://memory', 'rb+');

        $this->writeToStream($stream);
        rewind($stream);

        return $stream;
    }

    /**
     * Write the contents of the file to the given stream.
     *
     * @param resource $resource
     *
     * @return void
     */
    function writeToStream(mixed $resource): void {
        $this->fileVersionService->writeContentsToStream(
            $this->file,
            $this->fileVersion,
            $resource
        );
    }

    function put(mixed $updateResource): string {
        if ($this->authBackend->getAuthenticatedWebDavUser()->readonly) {
            throw new DAV\Exception\Forbidden();
        }

        $this->fileVersionService->updateLatestVersion(
            $this->file,
            $updateResource
        );

        $this->fileVersion->refresh();

        return $this->getETag();
    }

    function getSize(): int {
        return $this->fileVersion->bytes;
    }

    function getETag(): string {
        return "\"{$this->fileVersion->checksum}\"";
    }

    function getContentType(): string {
        return $this->fileVersion->mime_type;
    }

    function getLastModified(): int {
        return $this->fileVersion->file_updated_at->timestamp;
    }

    function getLastModifiedDateTime(): CarbonInterface {
        return $this->fileVersion->file_updated_at;
    }
}
