<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Directory>
 */
class DirectoryFactory extends Factory {
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'uuid' => fake()->uuid(),
            'user_id' => User::factory(),
            'parent_directory_id' => null,
            'name' => fake()->words(2, true),
        ];
    }
}
