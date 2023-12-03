<?php

namespace Tests\Feature;

use App\Http\Controllers\NotificationController;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Tests\TestCase;

class NotificationTest extends TestCase {
    use LazilyRefreshDatabase;

    protected User $user;

    protected function setUp(): void {
        parent::setUp();

        $this->user = $this->createUser();

        $this->actingAs($this->user);
    }

    public function testIndexNotificationViewCanBeRendered(): void {
        $notifications = $this->sendTestNotifications(
            NotificationController::ITEMS_PER_PAGE
        );

        $response = $this->get('/notifications');

        $response->assertOk();

        foreach ($notifications as $notification) {
            $response->assertSee($notification->title);
            $response->assertSee($notification->body);
        }
    }

    public function testIndexNotificationPaginates(): void {
        $this->sendTestNotifications(
            NotificationController::ITEMS_PER_PAGE + 1
        );

        $response = $this->get('/notifications?page=2');

        $response->assertOk();

        $databaseNotifications = $this->user
            ->notifications()
            ->orderBy('id', 'asc')
            ->get();

        foreach ($databaseNotifications as $notification) {
            $response->assertDontSee($notification->title);
            $response->assertDontSee($notification->body);
        }

        $response->assertSee($databaseNotifications[0]->title);
        $response->assertSee($databaseNotifications[0]->body);
    }

    public function testIndexNotificationDoesNotShowOtherUsersNotifications(): void {
        $otherUser = $this->createUser();

        $notifications = $this->sendTestNotifications(1, user: $otherUser);

        $response = $this->get('/notifications');

        $response->assertOk();

        foreach ($notifications as $notification) {
            $response->assertDontSee($notification->title);
            $response->assertDontSee($notification->body);
        }
    }

    public function testShowNotificationRedirectsToCorrectIndexPage(): void {
        // 3 pages
        $this->sendTestNotifications(
            NotificationController::ITEMS_PER_PAGE * 2 + 1
        );

        $notification = $this->user->notifications()->first();

        // move to last page
        $notification->created_at = now()->subDays(1);
        $notification->save();

        $response = $this->get("/notifications/{$notification->id}");

        $response->assertRedirect(
            '/notifications?page=3#notification-' . $notification->id
        );
    }

    public function testShowNotificationFailsIfNotificationIsNotOwnedByUser(): void {
        $otherUser = $this->createUser();

        $this->sendTestNotifications(1, user: $otherUser);

        $notification = $otherUser->notifications()->first();

        $response = $this->get("/notifications/{$notification->id}");

        $response->assertNotFound();
    }

    public function testNotificationCanBeMarkedAsRead(): void {
        $this->sendTestNotifications();

        $notification = $this->user->notifications()->first();

        $this->assertFalse($notification->read());

        $response = $this->from(static::REDIRECT_TEST_ROUTE)->put(
            "/notifications/{$notification->id}",
            [
                'read' => true,
            ]
        );

        $response->assertRedirect(static::REDIRECT_TEST_ROUTE);

        $notification->refresh();

        $this->assertTrue($notification->read());
    }

    public function testNotificationCanBeMarkedAsUnread(): void {
        $this->sendTestNotifications();

        $notification = $this->user->notifications()->first();

        $notification->markAsRead();

        $this->assertTrue($notification->read());

        $response = $this->from(static::REDIRECT_TEST_ROUTE)->put(
            "/notifications/{$notification->id}",
            [
                'read' => false,
            ]
        );

        $response->assertRedirect(static::REDIRECT_TEST_ROUTE);

        $notification->refresh();

        $this->assertFalse($notification->read());
    }

    public function testNotificationCantBeMarkedIfIsNotOwnedByUser(): void {
        $otherUser = $this->createUser();

        $this->sendTestNotifications(1, user: $otherUser);

        $notification = $otherUser->notifications()->first();

        $this->assertFalse($notification->read());

        $response = $this->from(static::REDIRECT_TEST_ROUTE)->put(
            "/notifications/{$notification->id}",
            [
                'read' => true,
            ]
        );

        $response->assertNotFound();

        $notification->refresh();

        $this->assertFalse($notification->read());
    }

    public function testAllNotificationsCanBeMarkedAsReadForUser(): void {
        $amountNotifications = 3;
        $amountNotificationsOtherUser = 2;

        $this->sendTestNotifications($amountNotifications);

        $otherUser = $this->createUser();

        $this->sendTestNotifications(
            $amountNotificationsOtherUser,
            user: $otherUser
        );

        $this->assertEquals(
            $amountNotifications,
            $this->user->unreadNotifications()->count()
        );

        $response = $this->from(static::REDIRECT_TEST_ROUTE)->put(
            '/notifications'
        );

        $response->assertRedirect(static::REDIRECT_TEST_ROUTE);

        $this->assertEquals(0, $this->user->unreadNotifications()->count());
        $this->assertEquals(
            $amountNotificationsOtherUser,
            $otherUser->unreadNotifications()->count()
        );
    }

    public function testNotificationCanBeDeleted(): void {
        $this->sendTestNotifications();

        $notification = $this->user->notifications()->first();

        $response = $this->from(static::REDIRECT_TEST_ROUTE)->delete(
            "/notifications/{$notification->id}"
        );

        $response->assertRedirect(static::REDIRECT_TEST_ROUTE);

        $this->assertDatabaseMissing('notifications', [
            'id' => $notification->id,
        ]);
    }

    public function testNotificationCantBeDeletedIfIsNotOwnedByUser(): void {
        $otherUser = $this->createUser();

        $this->sendTestNotifications(1, user: $otherUser);

        $notification = $otherUser->notifications()->first();

        $response = $this->from(static::REDIRECT_TEST_ROUTE)->delete(
            "/notifications/{$notification->id}"
        );

        $response->assertNotFound();

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
        ]);
    }

    public function testAllNotificationsCanBeDeletedForUser(): void {
        $amountNotifications = 3;
        $amountNotificationsOtherUser = 2;

        $this->sendTestNotifications($amountNotifications);

        $otherUser = $this->createUser();

        $this->sendTestNotifications(
            $amountNotificationsOtherUser,
            user: $otherUser
        );

        $this->assertEquals(
            $amountNotifications,
            $this->user->notifications()->count()
        );

        $response = $this->from(static::REDIRECT_TEST_ROUTE)->delete(
            '/notifications'
        );

        $response->assertRedirect(static::REDIRECT_TEST_ROUTE);

        $this->assertEquals(0, $this->user->notifications()->count());
        $this->assertEquals(
            $amountNotificationsOtherUser,
            $otherUser->notifications()->count()
        );
    }

    protected function sendTestNotifications(
        int $amount = 1,
        ?User $user = null
    ): array {
        $notifications = [];

        for ($i = 0; $i < $amount; $i++) {
            $notification = new TestNotification(prefix: "Test {$i} ");

            NotificationFacade::send($user ?? $this->user, $notification);

            $notifications[] = $notification;
        }

        return $notifications;
    }
}

class TestNotification extends Notification {
    public readonly string $title;
    public readonly string $body;

    public function __construct(string $prefix = '') {
        $this->title = $prefix . fake()->words(3, true);
        $this->body = $prefix . fake()->paragraph(3);
    }

    public function via(object $notifiable): array {
        return ['database'];
    }

    public function toArray(mixed $notifiable): array {
        return [
            'title' => $this->title,
            'body' => $this->body,
        ];
    }
}
