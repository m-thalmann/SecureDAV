<?php

namespace App\WebDav\Filesystem;

use App\Models\File;
use App\Services\FileVersionService;
use App\WebDav\AuthBackend;

/**
 * Virtual directory which contains all files, ignoring any directory structure.
 * The files are accessible through a separate directory for each one, having the uuid of the file as it's name.
 */
class VirtualAllFilesDirectory extends AbstractVirtualDirectory {
    public function __construct(
        protected AuthBackend $authBackend,
        protected FileVersionService $fileVersionService
    ) {
    }

    public function getName(): string {
        return static::BASE_NAME;
    }

    protected function loadChildren(): array {
        return $this->authBackend
            ->getAuthenticatedWebDavUser()
            ->files()
            ->has('latestVersion')
            ->get()
            ->map(function (File $file) {
                return new VirtualUuidFileContainer(
                    $this->authBackend,
                    $this->fileVersionService,
                    $file
                );
            })
            ->all();
    }
}
