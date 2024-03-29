<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BackupConfiguration>
 */
class BackupConfigurationFactory extends Factory {
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'uuid' => fake()->uuid(),
            // provider_class must be set explicitly
            'user_id' => User::factory(),
            'label' => fake()->words(2, true),
            'store_with_version' => false,
            'config' => null,
            'active' => true,
        ];
    }
}
