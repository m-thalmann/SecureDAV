<?php

namespace App\Support;

class FileInfo {
    public function __construct(
        public readonly string $path,
        public readonly ?string $mimeType,
        public readonly int $size,
        public readonly string $checksum
    ) {
    }

    /**
     * Create a new FileInfo instance from a open file resource.
     *
     * @param string $path
     * @param mixed $resource
     *
     * @return \App\Support\FileInfo
     */
    public static function fromResource(string $path, mixed $resource): static {
        $mimeType = mime_content_type($resource);
        rewind($resource);

        $fstat = fstat($resource);
        $size = $fstat['size'];

        $hashCtx = hash_init('md5');
        hash_update_stream($hashCtx, $resource);
        $checksum = hash_final($hashCtx);

        return new FileInfo($path, $mimeType, $size, $checksum);
    }
}
