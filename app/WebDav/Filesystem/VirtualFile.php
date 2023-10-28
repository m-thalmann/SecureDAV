<?php

namespace App\WebDav\Filesystem;

use App\Models\File;
use App\Models\FileVersion;
use App\Services\FileVersionService;
use App\WebDav\AuthBackend;
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
        $stream = fopen('php://memory', 'r+');

        $this->fileVersionService->writeContentsToStream(
            $this->file,
            $this->fileVersion,
            $stream
        );

        rewind($stream);

        return $stream;
    }

    function getSize(): int {
        return $this->fileVersion->bytes;
    }

    function getETag(): string {
        return "\"{$this->fileVersion->checksum}\"";
    }

    function getContentType(): string {
        return $this->file->mime_type;
    }

    function getLastModified(): int {
        return $this->fileVersion->updated_at->timestamp;
    }
}
