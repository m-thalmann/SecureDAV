<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NotStringContains implements ValidationRule {
    protected string $illegalCharactersString;

    public function __construct(array $illegalCharacters) {
        $this->illegalCharactersString = join('', $illegalCharacters);
    }

    public function validate(
        string $attribute,
        mixed $value,
        Closure $fail
    ): void {
        $substr = strpbrk($value, $this->illegalCharactersString);

        if ($substr !== false) {
            $fail(
                'The :attribute field contains an invalid character (:char).'
            )->translate(['char' => $substr[0]]);
        }
    }
}

