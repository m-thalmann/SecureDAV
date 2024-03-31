<?php

namespace Tests\Unit\Actions;

use App\Actions\CreateUser;
use App\Models\File;
use App\Services\FileVersionService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class CreateUserTest extends TestCase {
    use LazilyRefreshDatabase;

    protected FileVersionService|MockInterface $fileVersionServiceMock;
    protected CreateUser $action;

    protected function setUp(): void {
        parent::setUp();

        $this->fileVersionServiceMock = Mockery::mock(
            FileVersionService::class
        );

        $this->action = new CreateUser($this->fileVersionServiceMock);
    }

    public function testValidatesInput(): void {
        $name = 'Test User';
        $email = 'not-an-email';
        $password = '';

        $this->expectException(
            \Illuminate\Validation\ValidationException::class
        );

        try {
            $this->action->handle(
                $name,
                $email,
                $password,
                $password,
                isAdmin: false
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('email', $e->errors());
            $this->assertArrayHasKey('password', $e->errors());
            throw $e;
        }
    }

    public function testValidatesDuplicateEmail(): void {
        $user = $this->createUser();

        $this->expectException(
            \Illuminate\Validation\ValidationException::class
        );

        try {
            $this->action->handle(
                $user->name,
                $user->email,
                'password',
                'password',
                isAdmin: false
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('email', $e->errors());
            throw $e;
        }
    }

    public function testCreatesUser(): void {
        $name = 'Test User';
        $email = 'jane.doe@example.com';
        $password = 'password';

        $this->fileVersionServiceMock->shouldNotReceive('createNewVersion');

        $user = $this->action->handle(
            $name,
            $email,
            $password,
            $password,
            isAdmin: false,
            createReadme: false
        );

        $this->assertNotNull($user);

        $this->assertDatabaseHas('users', [
            'name' => $name,
            'email' => $email,
            'is_admin' => false,
        ]);

        $this->assertTrue(Hash::check($password, $user->password));
    }

    public function testCreatesUserWithReadmeAndDirectories(): void {
        $name = 'Test User';
        $email = 'jane.doe@example.com';
        $password = 'password';

        $this->fileVersionServiceMock
            ->shouldReceive('createNewVersion')
            ->withArgs(function (File $file, mixed $resource, bool $encrypted) {
                $this->assertEquals('README.md', $file->name);
                $this->assertFalse($encrypted);

                $this->assertIsResource($resource);
                $this->assertNotEmpty(stream_get_contents($resource));

                return true;
            })
            ->once();

        $user = $this->action->handle(
            $name,
            $email,
            $password,
            $password,
            isAdmin: true,
            createReadme: true
        );

        $this->assertDatabaseHas('users', [
            'email' => $email,
            'is_admin' => true,
        ]);

        $this->assertDatabaseHas('files', [
            'user_id' => $user->id,
            'name' => 'README.md',
        ]);

        foreach (CreateUser::DIRECTORIES as $directory) {
            $this->assertDatabaseHas('directories', [
                'user_id' => $user->id,
                'name' => $directory,
            ]);
        }
    }
}
