<?php

namespace Tests\TestSupport;

/**
 * A stream wrapper that only allows a single write operation.
 */
class SingleWriteStreamWrapper {
    protected int $pos = 0;
    protected int $writeCount = 0;
    public mixed $context;

    public function stream_open(string $path, string $mode, int $options, mixed &$opened_path): bool {
        $this->pos = 0;
        return true;
    }

    public function stream_read(int $count): string {
        $this->pos += $count;
        return "";
    }

    public function stream_write(string $data): int|false {
        if($this->writeCount >= 1) {
            return false;
        }

        $this->writeCount++;

        $len = strlen($data);

        $this->pos += $len;

        return $len;
    }

    public function stream_tell(): int {
        return $this->pos;
    }

    public function stream_eof(): bool {
        return true;
    }

    public function stream_seek(int $offset, string $whence): int|false {
        return false;
    }
}