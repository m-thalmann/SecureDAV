<?php

namespace App\Rules;

use App\Models\Directory;
use App\Models\File;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueFileName implements ValidationRule {
    public function __construct(
        protected int $forUserId,
        protected ?int $inDirectoryId = null,
        protected ?File $ignoreFile = null,
        protected ?Directory $ignoreDirectory = null
    ) {
    }

    public function validate(
        string $attribute,
        mixed $value,
        Closure $fail
    ): void {
        if (
            $this->fileWithNameExists($value) ||
            $this->directoryWithNameExists($value)
        ) {
            $fail(
                'A file or directory with the name in the :attribute field exists already.'
            )->translate();
        }
    }

    protected function fileWithNameExists(mixed $name): bool {
        return File::query()
            ->where('name', $name)
            ->where('directory_id', $this->inDirectoryId)
            ->where('user_id', $this->forUserId)
            ->when(
                $this->ignoreFile,
                fn($query) => $query->where('id', '!=', $this->ignoreFile->id)
            )
            ->exists();
    }

    protected function directoryWithNameExists(mixed $name): bool {
        return Directory::query()
            ->where('name', $name)
            ->where('parent_directory_id', $this->inDirectoryId)
            ->where('user_id', $this->forUserId)
            ->when(
                $this->ignoreDirectory,
                fn($query) => $query->where(
                    'id',
                    '!=',
                    $this->ignoreDirectory->id
                )
            )
            ->exists();
    }
}
