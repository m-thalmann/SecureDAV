<?php

namespace Database\Factories;

use App\Models\File;
use App\Models\FileVersion;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        return [
            'file_id' => File::factory(),
            'label' => null,
            'mime_type' => 'text/plain',
            'encryption_key' => null,
            'version' => 1,
            'storage_path' => null,
            'checksum' => $this->faker->md5(),
            'bytes' => $this->faker->numberBetween(1, 1000000),
            'file_updated_at' => now(),
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

    /**
     * Configure the model factory.
     */
    public function configure(): static {
        return $this->afterMaking(function (FileVersion $fileVersion) {
            $file = $fileVersion->file;

            $fileVersion->version = $file->next_version;
            $file
                ->forceFill([
                    'next_version' => $file->next_version + 1,
                ])
                ->save();

            if ($fileVersion->storage_path !== null) {
                return;
            }

            $path = Str::uuid()->toString();
            $content = $this->faker->text(50);

            Storage::disk('files')->put($path, $content);

            $fileVersion->storage_path = $path;
            $fileVersion->checksum = md5($content);
            $fileVersion->bytes = strlen($content);
        });
    }
}
