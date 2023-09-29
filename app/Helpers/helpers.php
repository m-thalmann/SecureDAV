<?php

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
        if ($size > 0) {
            $size = (int) $size;
            $base = log($size) / log(1024);
            $suffixes = ['B', 'KB', 'MB', 'GB', 'TB'];

            return round(pow(1024, $base - floor($base)), $precision) .
                ' ' .
                $suffixes[floor($base)];
        } else {
            return $size;
        }
    }
}
