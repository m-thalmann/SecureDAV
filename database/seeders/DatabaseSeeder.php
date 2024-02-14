<?php

namespace Database\Seeders;

use App\Models\Directory;
use App\Models\File;
use App\Models\FileVersion;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder {
    /**
     * Seed the application's database.
     */
    public function run(): void {
        $user = User::factory()->create([
            'email' => 'john.doe@example.com',
            'name' => 'John Doe',
            'is_admin' => true,
        ]);

        $directory = Directory::factory()
            ->for($user)
            ->create();

        $files = collect();

        $files->concat(
            File::factory(4)
                ->for($user)
                ->for($directory)
                ->has(FileVersion::factory(), 'versions')
                ->create()
        );

        $files->concat(
            File::factory(2)
                ->for($user)
                ->has(FileVersion::factory(), 'versions')
                ->create(['directory_id' => null])
        );
    }
}

