<?php

namespace Database\Factories;

use App\Models\Directory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

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
            'name' => fake()->words(4, true),
            'description' => null,
            'mime_type' => fake()->mimeType(),
            'extension' => fake()->fileExtension(),
            'encrypted' => false,
            'next_version' => 1,
        ];
    }
}

