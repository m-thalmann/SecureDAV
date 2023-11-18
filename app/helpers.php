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

if (!function_exists('formatHours')) {
    /**
     * Format hours to days, hours, minutes
     * Example: 1.5 hours => 1 hour, 30 minutes
     *
     * @param float $hours The hours to format
     *
     * @return string
     */
    function formatHours(float $hours): string {
        $minutes = round(($hours - floor($hours)) * 60);
        $days = floor($hours / 24);
        $hours = (int) $hours - $days * 24;

        $minutesString =
            $minutes > 0
                ? trans_choice('{1} 1 minute|[2,*] :count minutes', $minutes)
                : null;
        $hoursString =
            $hours > 0
                ? trans_choice('{1} 1 hour|[2,*] :count hours', $hours)
                : null;
        $daysString =
            $days > 0
                ? trans_choice('{1} 1 day|[2,*] :count days', $days)
                : null;

        $strings = array_filter([$daysString, $hoursString, $minutesString]);

        return join(', ', $strings);
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

if (!function_exists('processFile')) {
    /**
     * Opens a file and passes it to a callback function for processing.
     * After the callback function has finished (or an exception occurs), the file is closed.
     *
     * @param string $path The path to the file to open.
     * @param Closure $callback The callback function to process the file.
     * @param Closure|null $exceptionCallback An optional callback function to handle any exceptions thrown during processing.
     * @param string $mode The mode to use when opening the file (default 'rb').
     *
     * @return mixed The result of the callback function.
     */
    function processFile(
        string $path,
        Closure $callback,
        ?Closure $exceptionCallback = null,
        string $mode = 'rb'
    ): mixed {
        $file = fopen($path, $mode);

        $returnValue = null;

        try {
            $returnValue = $callback($file);
        } catch (Exception $e) {
            fclose($file);

            if ($exceptionCallback !== null) {
                $exceptionCallback($e);
            }

            throw $e;
        }

        fclose($file);

        return $returnValue;
    }
}

if (!function_exists('previousUrl')) {
    /**
     * Returns the previous url or the fallback url, if the previous url is not set or is the same as the current.
     *
     * @param string|null $fallback The fallback url to use if no previous url is set.
     *
     * @return string
     */
    function previousUrl(?string $fallback = null): string {
        $url = url()->previous($fallback);

        if ($url === url()->current() && $fallback) {
            $url = $fallback;
        }

        return $url;
    }
}

