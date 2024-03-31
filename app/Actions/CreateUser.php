<?php

namespace App\Actions;

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use App\Services\FileVersionService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CreateUser {
    use PasswordValidationRules;

    public const DIRECTORIES = ['Documents', 'Media', 'Vaults'];

    public function __construct(
        protected FileVersionService $fileVersionService
    ) {
    }

    public function handle(
        string $name,
        string $email,
        string $password,
        string $passwordConfirmation,
        bool $isAdmin,
        bool $createReadme = true
    ): User {
        $data = Validator::make(
            [
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'password_confirmation' => $passwordConfirmation,
            ],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    Rule::unique(User::class, 'email'),
                ],
                'password' => $this->passwordRules(),
            ]
        )->validate();

        $user = User::make($data);
        $user->encryption_key = Str::random(16);

        $user->is_admin = $isAdmin;

        $user->save();

        if ($createReadme) {
            $this->createReadme($user);
        }

        return $user;
    }

    protected function createReadme(User $user): void {
        $file = $user->files()->create([
            'name' => 'README.md',
            'directory_id' => null,
        ]);

        processResource(
            fopen(app_path('Stubs/UserReadme.md'), 'r'),
            fn(
                mixed $fileResource
            ) => $this->fileVersionService->createNewVersion(
                $file,
                $fileResource,
                encrypted: false
            )
        );

        foreach (static::DIRECTORIES as $directory) {
            $user->directories()->create([
                'name' => $directory,
            ]);
        }
    }
}
