<?php

namespace App\WebDav\Filesystem;

use App\Models\File;
use App\Services\FileVersionService;
use App\WebDav\AuthBackend;

/**
 * Virtual directory which contains the given file as the single child.
 * The name will be the uuid of the file.
 */
class VirtualUuidFileContainer extends AbstractVirtualDirectory {
    public function __construct(
        protected AuthBackend $authBackend,
        protected FileVersionService $fileVersionService,
        public readonly File $file
    ) {
    }

    public function getName(): string {
        return $this->file->uuid;
    }

    protected function loadChildren(): array {
        return [
            new VirtualFile(
                $this->authBackend,
                $this->fileVersionService,
                $this->file
            ),
        ];
    }
}
