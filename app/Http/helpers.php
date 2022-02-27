<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

if (!function_exists("generateNameInitials")) {
    function generateNameInitials(string $name): string {
        $names = explode(" ", $name);
        $initials = "";

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

if (!function_exists("formatBytes")) {
    /**
     * Format bytes to kb, mb, gb, tb
     *
     * @param integer $size The size in bytes
     * @param integer $precision The precision to display
     *
     * @return string
     */
    function formatBytes($size, $precision = 2): string {
        if ($size > 0) {
            $size = (int) $size;
            $base = log($size) / log(1024);
            $suffixes = ["B", "KB", "MB", "GB", "TB"];

            return round(pow(1024, $base - floor($base)), $precision) .
                " " .
                $suffixes[floor($base)];
        } else {
            return $size;
        }
    }
}
