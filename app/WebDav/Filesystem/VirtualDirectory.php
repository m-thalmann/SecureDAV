<?php

namespace App\WebDav\Filesystem;

use App\Models\Directory;
use App\Models\File;
use App\Services\FileVersionService;
use App\WebDav\AuthBackend;

/**
 * Virtual directory which contains all files and directories inside of the given directory for the authenticated user.
 * Only files that are accessible by the access group and which have a version will be included.
 * All directories for the user will be included.
 */
class VirtualDirectory extends AbstractVirtualDirectory {
    public function __construct(
        protected AuthBackend $authBackend,
        protected FileVersionService $fileVersionService,
        public readonly ?Directory $directory
    ) {
    }

    public function getName(): string {
        return $this->directory?->name ?? static::BASE_NAME;
    }

    protected function loadChildren(): array {
        $directories = $this->loadDirectories();
        $files = $this->loadFiles();

        return [...$directories, ...$files];
    }

    protected function loadDirectories(): array {
        return $this->authBackend
            ->getAuthenticatedUser()
            ->directories()
            ->inDirectory($this->directory, filterUser: false)
            ->get()
            ->map(function (Directory $directory) {
                return new VirtualDirectory(
                    $this->authBackend,
                    $this->fileVersionService,
                    $directory
                );
            })
            ->all();
    }

    protected function loadFiles(): array {
        return $this->authBackend
            ->getAuthenticatedAccessGroup()
            ->files()
            ->inDirectory($this->directory, filterUser: false)
            ->has('latestVersion')
            ->with('latestVersion')
            ->get()
            ->map(function (File $file) {
                return new VirtualFile(
                    $this->authBackend,
                    $this->fileVersionService,
                    $file
                );
            })
            ->all();
    }
}
