<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use App\Support\SessionMessage;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase {
    use LazilyRefreshDatabase;

    protected User $user;

    protected function setUp(): void {
        parent::setUp();

        $this->user = $this->createUser();

        $this->passwordConfirmed();
    }

    public function testTwoFactorAuthenticationCanBeEnabledAndConfirmedForUser(): void {
        $this->actingAs($this->user);

        $this->assertFalse($this->user->hasEnabledTwoFactorAuthentication());

        $enableResponse = $this->from('/settings/profile')->post(
            '/user/two-factor-authentication'
        );

        $enableResponse->assertRedirect(
            '/settings/profile#two-factor-authentication'
        );

        $tfaEngine = app(Google2FA::class);
        $validOtp = $tfaEngine->getCurrentOtp(
            decrypt($this->user->two_factor_secret)
        );

        $confirmResponse = $this->from('/settings/profile')->post(
            '/user/confirmed-two-factor-authentication',
            [
                'code' => $validOtp,
            ]
        );

        $confirmResponse->assertRedirect(
            '/settings/profile#two-factor-authentication'
        );

        $confirmResponse->assertSessionHas('two-factor-confirmed', true);
        $this->assertRequestHasSessionMessage(
            $confirmResponse,
            SessionMessage::TYPE_SUCCESS
        );

        $this->assertTrue($this->user->hasEnabledTwoFactorAuthentication());
    }

    public function testConfirmingTwoFactorAuthenticationFailsWithInvalidCode(): void {
        $this->actingAs($this->user);

        $this->enableTwoFactorForUser(confirmed: false);

        $response = $this->from('/settings/profile')->post(
            '/user/confirmed-two-factor-authentication',
            [
                'code' => 'invalid-code',
            ]
        );

        $response->assertRedirect(
            '/settings/profile#two-factor-authentication'
        );

        $response->assertSessionHasErrorsIn(
            'confirmTwoFactorAuthentication',
            'code'
        );

        $this->assertFalse($this->user->hasEnabledTwoFactorAuthentication());
    }

    public function testTwoFactorAuthenticationCanBeDisabledForUser(): void {
        $this->actingAs($this->user);

        $this->enableTwoFactorForUser();

        $this->assertTrue($this->user->hasEnabledTwoFactorAuthentication());

        $response = $this->from('/settings/profile')->delete(
            '/user/two-factor-authentication'
        );

        $response->assertRedirect(
            '/settings/profile#two-factor-authentication'
        );

        $this->assertFalse($this->user->hasEnabledTwoFactorAuthentication());
    }

    public function testRecoveryCodesCanBeRegenerated(): void {
        $this->actingAs($this->user);

        $this->enableTwoFactorForUser();

        $recoveryCodes = decrypt($this->user->two_factor_recovery_codes);

        $response = $this->from('/settings/profile')->post(
            '/user/two-factor-recovery-codes'
        );

        $response->assertRedirect(
            '/settings/profile#two-factor-authentication'
        );

        $response->assertSessionHas(
            'two-factor-recovery-codes-regenerated',
            true
        );

        $this->assertNotEquals(
            $recoveryCodes,
            decrypt($this->user->fresh()->two_factor_recovery_codes)
        );
    }

    public function testTwoFactorAuthenticationIsRequestedWhenEnabledForUser(): void {
        $this->enableTwoFactorForUser();

        $response = $this->post('/login', [
            'email' => $this->user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/two-factor-challenge');
    }

    public function testTwoFactorAuthenticationSucceedsWithValidCode(): void {
        $this->enableTwoFactorForUser();

        $this->post('/login', [
            'email' => $this->user->email,
            'password' => 'password',
        ]);

        $tfaEngine = app(Google2FA::class);
        $validOtp = $tfaEngine->getCurrentOtp(
            decrypt($this->user->two_factor_secret)
        );

        $response = $this->post('/two-factor-challenge', [
            'code' => $validOtp,
        ]);

        $response->assertRedirect(RouteServiceProvider::HOME);
    }

    public function testTwoFactorAuthenticationIsRateLimited(): void {
        $this->enableTwoFactorForUser();

        $this->post('/login', [
            'email' => $this->user->email,
            'password' => 'password',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $response = $this->post('/two-factor-challenge', [
                'code' => 'invalid-code',
            ]);

            $response->assertSessionHasErrors(['code']);
        }

        $response = $this->post('/two-factor-challenge', [
            'code' => 'invalid-code',
        ]);

        $response->assertTooManyRequests();
    }

    protected function enableTwoFactorForUser(bool $confirmed = true): void {
        $this->user
            ->forceFill([
                'two_factor_secret' => encrypt(
                    app(Google2FA::class)->generateSecretKey()
                ),
                'two_factor_recovery_codes' => encrypt(
                    json_encode(['TEST-CODE'])
                ),
                'two_factor_confirmed_at' => $confirmed ? now() : null,
            ])
            ->save();
    }
}

