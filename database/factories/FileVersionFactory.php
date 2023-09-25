<?php

namespace Database\Factories;

use App\Models\File;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\Testing\File as TestingFile;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FileVersion>
 */
class FileVersionFactory extends Factory {
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        // TODO: improve factory
        $file = File::factory()->create();

        $path = TestingFile::create($file->name)->store('files');

        return [
            'file_id' => $file->id,
            'label' => null,
            'version' => 1,
            'storage_path' => $path,
            'etag' => $this->faker->md5(),
            'bytes' => $this->faker->numberBetween(1, 1000000),
        ];
    }
}

