<?php

namespace Tests\Unit\Commands;

use App\Actions\CreateUser;
use App\Models\User;
use Exception;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery\MockInterface;
use Tests\TestCase;

class CreateAdminTest extends TestCase {
    use LazilyRefreshDatabase;

    protected CreateUser|MockInterface $createUserActionMock;

    protected function setUp(): void {
        parent::setUp();

        $this->createUserActionMock = $this->mock(CreateUser::class);
    }

    public function testAsksForUserCredentialsAndCreatesUser(): void {
        Event::fake([Registered::class]);

        $name = 'Admin';
        $email = 'admin@example.com';
        $password = 'password';
        $passwordConfirmation = 'confirmation password';

        $mockUser = new User();

        $this->createUserActionMock
            ->shouldReceive('handle')
            ->with($name, $email, $password, $passwordConfirmation, true, true)
            ->once()
            ->andReturn($mockUser);

        $this->artisan('app:create-admin')
            ->expectsQuestion('Admin name', $name)
            ->expectsQuestion('Admin email', $email)
            ->expectsQuestion('Admin password', $password)
            ->expectsQuestion('Confirm admin password', $passwordConfirmation)
            ->assertSuccessful();

        Event::assertDispatched(Registered::class, function (
            Registered $event
        ) use ($mockUser) {
            $this->assertEquals($mockUser, $event->user);

            return true;
        });
    }

    public function testPrintsMessageIfEmailVerificationIsEnabled(): void {
        Event::fake([Registered::class]);

        config(['app.email_verification_enabled' => true]);

        $this->createUserActionMock
            ->shouldReceive('handle')
            ->once()
            ->andReturn(new User());

        $this->artisan('app:create-admin')
            ->expectsQuestion('Admin name', 'Admin')
            ->expectsQuestion('Admin email', 'admin@example.com')
            ->expectsQuestion('Admin password', 'password')
            ->expectsQuestion('Confirm admin password', 'password')
            ->expectsOutput('✔ Admin user created successfully')
            ->expectsOutput('✔ Admin user email verification sent')
            ->assertSuccessful();

        Event::assertDispatched(Registered::class);
    }

    public function testFailsToCreateUserAndDisplaysErrorMessage(): void {
        Event::fake([Registered::class]);

        $exception = new Exception('User creation failed');

        $this->createUserActionMock
            ->shouldReceive('handle')
            ->once()
            ->andThrow($exception);

        $this->artisan('app:create-admin')
            ->expectsQuestion('Admin name', 'Admin')
            ->expectsQuestion('Admin email', 'admin@example.com')
            ->expectsQuestion('Admin password', 'password')
            ->expectsQuestion('Confirm admin password', 'password')
            ->expectsOutput('✗ ' . $exception->getMessage())
            ->assertFailed();

        Event::assertNotDispatched(Registered::class);
    }
}
