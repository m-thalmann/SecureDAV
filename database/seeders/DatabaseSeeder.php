<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder {
    private const DIRECTORIES = ['Documents', 'Media', 'Vaults'];

    private const README_TEXT = <<<'EOT'
# Welcome to SecureDAV

SecureDAV is a secure file storage which also acts as a WebDAV server with specific access controls.

## Getting Started

> We've already setup a basic folder structure for you, but feel free to customize it to your needs!

To get started create a new file by clicking the "+" button at the top. You can choose to encrypt the file on the server, for added security.

Next you should setup a new WebDav user. A WebDav user has a specific set of files it can access via WebDav. It also has it's own username and password and can either be readonly or have write permissions
(you can't access WebDav with your default SecureDAV user account for security reasons). After you've successfully created your first WebDav user make sure to attach your file to it.

Now you can access your file via the link visible in the file management view using your preferred WebDav client.

## Versions

Each file can have multiple versions. Think of them as "checkpoints" or "snapshots" of a file at a given time.

Versions can be created manually by copying the latest one or uploading a new version from your local disk. It is also possible to create a new version automatically when the file was changed and a certain amount of time has passed. For this simply select your desired "Auto version delay" at the top right of the versions card.
EOT;

    public function run(): void {
        $user = User::factory()->create([
            'email' => 'john.doe@example.com',
            'name' => 'John Doe',
            'is_admin' => true,
        ]);

        foreach (static::DIRECTORIES as $directory) {
            $user->directories()->create([
                'name' => $directory,
            ]);
        }

        $path = Str::uuid()->toString();
        Storage::disk('files')->put($path, static::README_TEXT);

        $file = $user->files()->create([
            'name' => 'README.md',
            'directory_id' => null,
            'next_version' => 2,
        ]);

        $file->versions()->create([
            'version' => 1,
            'mime_type' => 'text/markdown',
            'bytes' => strlen(static::README_TEXT),
            'checksum' => md5(static::README_TEXT),
            'storage_path' => $path,
            'file_updated_at' => now(),
        ]);
    }
}
