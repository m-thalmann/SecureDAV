<?php

namespace Tests\Unit\Commands;

use App\WebDav\LocksBackend;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CleanDatabaseTest extends TestCase {
    use LazilyRefreshDatabase;

    public function testRemovesExpiredWebDavLocks(): void {
        $timeout = 100;

        $user = $this->createUser();

        $expiredLockId = DB::table(LocksBackend::TABLE_NAME)->insertGetId([
            'user_id' => $user->id,
            'owner' => 'test',
            'timeout' => $timeout,
            'scope' => 1,
            'depth' => 1,
            'uri' => 'test/uri',
            'created' => time() - $timeout,
            'token' => 'token1',
        ]);

        $notExpiredLockId = DB::table(LocksBackend::TABLE_NAME)->insertGetId([
            'user_id' => $user->id,
            'owner' => 'test',
            'timeout' => $timeout,
            'scope' => 1,
            'depth' => 1,
            'uri' => 'test/uri',
            'created' => time() - $timeout + 1,
            'token' => 'token2',
        ]);

        $this->artisan('clean-database')
            ->expectsOutputToContain('Deleted 1 expired WebDAV locks.')
            ->assertSuccessful();

        $this->assertDatabaseMissing(LocksBackend::TABLE_NAME, [
            'id' => $expiredLockId,
        ]);
        $this->assertDatabaseHas(LocksBackend::TABLE_NAME, [
            'id' => $notExpiredLockId,
        ]);
    }

    public function testRemovesOldNotifications(): void {
        $user = $this->createUser();

        $oldNotification = $user->notifications()->create([
            'id' => fake()->uuid(),
            'type' => 'test',
            'data' => 'test data',
            'read_at' => now()->subWeek(),
        ]);

        $notOldNotification = $user->notifications()->create([
            'id' => fake()->uuid(),
            'type' => 'test',
            'data' => 'test data',
            'read_at' => now()
                ->subWeek()
                ->addDay(),
        ]);

        $this->artisan('clean-database')
            ->expectsOutputToContain('Deleted 1 old notifications.')
            ->assertSuccessful();

        $this->assertDatabaseMissing('notifications', [
            'id' => $oldNotification->id,
        ]);
        $this->assertDatabaseHas('notifications', [
            'id' => $notOldNotification->id,
        ]);
    }
}
