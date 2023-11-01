<?php

namespace App\Support;

use Illuminate\Filesystem\FilesystemAdapter;
use InvalidArgumentException;

class FileInfo {
    public function __construct(
        public readonly string $path,
        public readonly ?string $mimeType,
        public readonly int $size,
        public readonly string $checksum
    ) {
    }

    /**
     * Create a new FileInfo instance from a file stored inside the storage.
     *
     * @param \Illuminate\Filesystem\FilesystemAdapter $storage
     * @param string $path
     *
     * @throws \InvalidArgumentException If the file does not exist.
     *
     * @return \App\Support\FileInfo
     */
    public static function fromStorage(
        FilesystemAdapter $storage,
        string $path
    ): static {
        if (!$storage->exists($path)) {
            throw new InvalidArgumentException("File does not exist: $path");
        }

        $fullPath = $storage->path($path);

        $mimeType =
            $storage->mimeType($path) ?: mime_content_type($fullPath) ?: null;

        return new FileInfo(
            $fullPath,
            $mimeType,
            $storage->size($path),
            md5_file($fullPath)
        );
    }
}
