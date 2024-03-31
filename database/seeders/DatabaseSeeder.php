<?php

namespace Database\Seeders;

use App\Actions\CreateUser;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder {
    public function __construct(protected CreateUser $createUserAction) {
    }

    public function run(): void {
        $this->createUserAction->handle(
            'Admin',
            'admin@example.com',
            'password',
            'password',
            isAdmin: true,
            createReadme: true
        );
    }
}
