<?php

namespace App\Console\Commands;

use App\Actions\CreateUser;
use App\Models\User;
use Exception;
use Illuminate\Auth\Events\Registered;
use Illuminate\Console\Command;

class CreateAdmin extends Command {
    protected $signature = 'app:create-admin';

    protected $description = 'Create an admin user for the application';

    public function __construct(protected CreateUser $createUserAction) {
        parent::__construct();
    }

    public function handle(): int {
        $this->alert('SECUREDAV CREATE ADMIN USER');

        $user = $this->createUser();

        if ($user === null) {
            return static::FAILURE;
        }

        $this->info('✔ Admin user created successfully');

        if (config('app.email_verification_enabled', true)) {
            // will be sent by the registered event
            $this->info('✔ Admin user email verification sent');
        }

        return static::SUCCESS;
    }

    protected function createUser(): ?User {
        $adminName = $this->ask('Admin name');
        $adminEmail = $this->ask('Admin email');
        $adminPassword = $this->secret('Admin password');
        $adminPasswordConfirmation = $this->secret('Confirm admin password');

        $user = null;

        try {
            $user = $this->createUserAction->handle(
                $adminName,
                $adminEmail,
                $adminPassword,
                $adminPasswordConfirmation,
                isAdmin: true,
                createReadme: true
            );

            event(new Registered($user));
        } catch (Exception $e) {
            $this->error('✗ ' . $e->getMessage());
        }

        return $user;
    }
}
