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

        $fileExtension = fake()->fileExtension();
        $fileName = fake()->words(4, true) . '.' . $fileExtension;

        return [
            'uuid' => fake()->uuid(),
            'user_id' => User::factory(),
            'directory_id' => $directory,
            'name' => $fileName,
            'description' => null,
            'mime_type' => fake()->mimeType(),
            'extension' => $fileExtension,
            'encrypted' => false,
        ];
    }
}

