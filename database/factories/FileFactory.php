<?php

namespace Database\Factories;

use App\Models\Directory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\File>
 */
class FileFactory extends Factory {
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'uuid' => fake()->uuid(),
            'user_id' => User::factory(),
            'directory_id' => fake()->boolean(25) ? Directory::factory() : null,
            'name' => fake()->words(4, true) . '.' . fake()->fileExtension(),
            'description' => null,
            'mime_type' => fake()->mimeType(),
            'encryption_key' => null,
            'next_version' => 1,
        ];
    }

    /**
     * Indicate that the model's encryption_key should be set
     */
    public function encrypted(): static {
        return $this->state(
            fn(array $attributes) => [
                'encryption_key' => Str::random(16),
            ]
        );
    }
}

