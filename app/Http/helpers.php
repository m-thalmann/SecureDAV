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
