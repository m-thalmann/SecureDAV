<?php

namespace Tests\Feature\Settings;

use App\Events\WebDavResumed;
use App\Events\WebDavSuspended;
use App\Models\User;
use App\Support\SessionMessage;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class WebDavSuspensionTest extends TestCase {
    use LazilyRefreshDatabase;

    protected User $user;

    protected function setUp(): void {
        parent::setUp();

        $this->user = $this->createUser();

        $this->actingAs($this->user);
    }

    public function testConfirmsPassword(): void {
        $response = $this->from(static::REDIRECT_TEST_ROUTE)->put(
            '/settings/webdav-suspension'
        );

        $response->assertRedirectToRoute('password.confirm');
    }

    public function testSuspendsWebDav(): void {
        Event::fake([WebDavSuspended::class]);

        $this->passwordConfirmed();
        $this->assertFalse($this->user->is_webdav_suspended);

        $response = $this->from(static::REDIRECT_TEST_ROUTE)->put(
            '/settings/webdav-suspension',
            ['suspended' => true]
        );

        $response->assertRedirect(static::REDIRECT_TEST_ROUTE);
        $this->assertResponseHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );

        $this->assertTrue($this->user->refresh()->is_webdav_suspended);

        Event::assertDispatched(WebDavSuspended::class, function (
            WebDavSuspended $event
        ) {
            $this->assertEquals($this->user->id, $event->user->id);

            return true;
        });
    }

    public function testResumesWebDav(): void {
        Event::fake([WebDavResumed::class]);

        $this->passwordConfirmed();
        $this->user->forceFill(['is_webdav_suspended' => true])->save();

        $this->assertTrue($this->user->is_webdav_suspended);

        $response = $this->from(static::REDIRECT_TEST_ROUTE)->put(
            '/settings/webdav-suspension',
            ['suspended' => false]
        );

        $response->assertRedirect(static::REDIRECT_TEST_ROUTE);
        $this->assertResponseHasSessionMessage(
            $response,
            SessionMessage::TYPE_SUCCESS
        );

        $this->assertFalse($this->user->fresh()->is_webdav_suspended);

        Event::assertDispatched(WebDavResumed::class, function (
            WebDavResumed $event
        ) {
            $this->assertEquals($this->user->id, $event->user->id);

            return true;
        });
    }
}
