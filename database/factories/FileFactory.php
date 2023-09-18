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
        $directory = null;

        if (fake()->boolean(25)) {
            $directory = Directory::factory();
        }

        $fileName = fake()->word();

        return [
            'uuid' => fake()->uuid(),
            'user_id' => User::factory(),
            'directory_id' => $directory,
            'display_name' => $fileName,
            'client_name' => $fileName,
            'description' => null,
            'mime_type' => fake()->mimeType(),
            'extension' => fake()->fileExtension(),
            'encrypted' => false,
        ];
    }
}

