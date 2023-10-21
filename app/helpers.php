<?php

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

if (!function_exists('formatBytes')) {
    /**
     * Format bytes to kb, mb, gb, tb
     *
     * @param int $size The size in bytes
     * @param int $precision The precision to display
     *
     * @return string
     */
    function formatBytes(int $size, int $precision = 2): string {
        if ($size <= 0) {
            return "$size B";
        }

        $size = (int) $size;
        $base = log($size) / log(1024);
        $suffixes = ['B', 'KB', 'MB', 'GB', 'TB'];

        $suffix = $suffixes[floor($base)];
        $value = round(pow(1024, $base - floor($base)), $precision);

        return "$value $suffix";
    }
}

if (!function_exists('generateInitials')) {
    /**
     * Generates the initials of the given name.
     *
     * @param string $name
     *
     * @return string
     */
    function generateInitials(string $name): string {
        $names = explode(' ', $name);
        $initials = '';

        if (count($names) >= 2) {
            $first = Arr::first($names)[0];
            $last = Arr::last($names)[0];

            $initials = $first . $last;
        } else {
            $initials = Str::substr($name, 0, 2);
        }

        return Str::upper($initials);
    }
}

if (!function_exists('getFileIconForExtension')) {
    /**
     * Returns the icon for the given file extension.
     *
     * @param string|null $extension
     *
     * @return string
     */
    function getFileIconForExtension(?string $extension): string {
        $defaultIcon = 'fas fa-file';

        if ($extension === null) {
            return $defaultIcon;
        }

        $extension = strtolower($extension);

        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'svg'])) {
            return 'fa-solid fa-file-image';
        }

        if (in_array($extension, ['mp4', 'webm', 'mov', 'avi', 'wmv', 'mkv'])) {
            return 'fa-solid fa-file-video';
        }

        if (in_array($extension, ['mp3', 'wav', 'ogg', 'wma'])) {
            return 'fa-solid fa-file-audio';
        }

        if (in_array($extension, ['doc', 'docx', 'odt', 'rtf', 'txt'])) {
            return 'fa-solid fa-file-word text-blue-600';
        }

        if (in_array($extension, ['xls', 'xlsx', 'ods', 'csv'])) {
            return 'fa-solid fa-file-excel text-green-600';
        }

        if (in_array($extension, ['pdf'])) {
            return 'fa-solid fa-file-pdf text-red-500';
        }

        if (in_array($extension, ['zip', 'rar', '7z', 'tar', 'gz', 'bz2'])) {
            return 'fa-solid fa-file-zipper';
        }

        if (in_array($extension, ['kdbx'])) {
            return 'fa-solid fa-file-shield';
        }

        return $defaultIcon;
    }
}

if (!function_exists('getTableLoopDropdownPositionAligned')) {
    /**
     * Returns the position of the dropdown for the given loop index.
     *
     * @param int $index The current loop index
     * @param int $totalItems The total number of items in the loop
     * @param int $dropdownHeightRows The height of the dropdown in rows (rounded up)
     *
     * @return string The position and align of the dropdown (<bottom|left>-<start|end>)
     */
    function getTableLoopDropdownPositionAligned(
        int $index,
        int $totalItems,
        int $dropdownHeightRows
    ): string {
        $rowsBefore = $index + 1; // plus the header row
        $rowsAfter = max($totalItems - $index - 1, 0);

        if ($rowsAfter >= $dropdownHeightRows) {
            return 'bottom-end';
        }

        if (
            $rowsAfter < $dropdownHeightRows - 1 &&
            $rowsBefore >= $dropdownHeightRows - 1
        ) {
            return 'left-end';
        }

        return 'left-start';
    }
}

if (!function_exists('authUser')) {
    /**
     * Returns the currently authenticated user.
     *
     * @param string|null $guard The guard to use
     *
     * @return User|null
     */
    function authUser(?string $guard = null): ?User {
        return auth($guard)->user();
    }
}
