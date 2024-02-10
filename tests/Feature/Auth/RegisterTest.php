<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use App\Support\SessionMessage;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegisterTest extends TestCase {
    use LazilyRefreshDatabase;

    protected function setUp(): void {
        parent::setUp();

        config(['app.registration_enabled' => true]);
    }

    public function testRegisterScreenCanBeRendered(): void {
        $response = $this->get('/register');

        $response->assertOk();
    }

    public function testRegisterScreenCantBeRenderedIfIsDisabled(): void {
        config(['app.registration_enabled' => false]);

        $response = $this->get('/register');

        $response->assertNotFound();
    }

    public function testUserCanRegister(): void {
        Event::fake([Registered::class]);

        $email = 'jane.doe@example.com';

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => $email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(RouteServiceProvider::HOME);

        $this->assertAuthenticated();

        Event::assertDispatched(Registered::class);

        $user = User::query()
            ->where('email', $email)
            ->first();

        $this->assertNotNull($user);

        $this->assertTrue(Hash::check('password', $user->password));

        $this->assertResponseHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );
    }

    public function testRegisterRedirectsToEmailVerificationIfIsEnabled(): void {
        Event::fake([Registered::class]);

        config(['app.email_verification_enabled' => true]);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'jane.doe@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect('/email/verify');

        $this->assertResponseHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );
    }

    public function testUserRegistrationFailsIfEmailAlreadyInUse(): void {
        $user = $this->createUser();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors(['email']);
    }
}
