<?php

namespace App\Concerns;

use Illuminate\Validation\Rules;

trait PasswordValidationRules {
    /**
     * Get the validation rules used to validate passwords.
     *
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    protected function passwordRules(): array {
        return ['required', 'string', 'confirmed', Rules\Password::defaults()];
    }
}
