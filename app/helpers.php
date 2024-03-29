<?php

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

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

        if (in_array($extension, ['doc', 'docx', 'odt', 'rtf', 'txt', 'md'])) {
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
     * @return \App\Models\User|null
     */
    function authUser(?string $guard = null): ?User {
        return auth($guard)->user();
    }
}

if (!function_exists('processResources')) {
    /**
     * Passes the resources to a callback function for processing.
     * After the callback function has finished (or an exception occurs), the resources are closed.
     *
     * @param array<resource> $resources The resources to process.
     * @param Closure $callback The callback function to process the resources.
     * @param Closure|null $exceptionCallback An optional callback function to handle any exceptions thrown during processing.
     *
     * @return mixed The result of the callback function.
     */
    function processResources(
        array $resources,
        Closure $callback,
        ?Closure $exceptionCallback = null
    ): mixed {
        $returnValue = null;

        try {
            $returnValue = $callback($resources);
        } catch (Exception $e) {
            foreach ($resources as $resource) {
                if (is_resource($resource)) {
                    fclose($resource);
                }
            }

            if ($exceptionCallback !== null) {
                $exceptionCallback($e);
            }

            throw $e;
        }

        foreach ($resources as $resource) {
            if (is_resource($resource)) {
                fclose($resource);
            }
        }

        return $returnValue;
    }
}

if (!function_exists('processResource')) {
    /**
     * Passes the resource to a callback function for processing.
     * After the callback function has finished (or an exception occurs), the resource is closed.
     *
     * @see processResources
     *
     * @param resource $resource The resource to process.
     * @param Closure $callback The callback function to process the resource.
     * @param Closure|null $exceptionCallback An optional callback function to handle any exceptions thrown during processing.
     *
     * @return mixed The result of the callback function.
     */
    function processResource(
        mixed $resource,
        Closure $callback,
        ?Closure $exceptionCallback = null
    ): mixed {
        return processResources(
            [$resource],
            fn(array $r) => $callback($r[0]),
            $exceptionCallback
        );
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

if (!function_exists('createStream')) {
    /**
     * Creates a temporary in-memory stream.
     * If a string is passed, the stream is filled with it.
     *
     * @param string|null $string
     *
     * @return resource
     */
    function createStream(?string $string = null): mixed {
        $stream = fopen('php://memory', 'r+');

        if ($string) {
            fwrite($stream, $string);
            rewind($stream);
        }

        return $stream;
    }
}

if (!function_exists('getTimezonesList')) {
    /**
     * Returns a list of timezones with their offsets.
     * @return array An array of timezones with their offsets.
     */
    function getTimezonesList(): array {
        return collect(DateTimeZone::listIdentifiers())
            ->map(function (string $timezone) {
                $offset = (new DateTimeZone($timezone))->getOffset(
                    new DateTime()
                );
                $offsetPrefix = $offset < 0 ? '-' : '+';

                $offset = gmdate('H:i', abs($offset));

                return [
                    'timezone' => $timezone,
                    'offset' => "UTC$offsetPrefix$offset",
                ];
            })
            ->sortBy('offset')
            ->toArray();
    }
}
