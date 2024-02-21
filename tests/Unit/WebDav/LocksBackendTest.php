<?php

namespace Tests\Unit\WebDav;

use App\Models\User;
use App\WebDav\AuthBackend;
use App\WebDav\LocksBackend;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Sabre\DAV\Locks\LockInfo;
use Tests\TestCase;

class LocksBackendTest extends TestCase {
    use LazilyRefreshDatabase;

    protected User $user;

    protected AuthBackend|MockInterface $authBackend;
    protected LocksBackendTestClass $locksBackend;

    protected function setUp(): void {
        parent::setUp();

        /**
         * @var AuthBackend|MockInterface
         */
        $this->authBackend = Mockery::mock(AuthBackend::class);
        $this->locksBackend = new LocksBackendTestClass($this->authBackend);

        $this->user = User::factory()->create(['id' => 41]);

        $this->authBackend
            ->shouldReceive('getAuthenticatedUser')
            ->andReturn($this->user);
    }

    public function testGetLocksReturnsLockForGivenPath(): void {
        $path = 'test/test2';
        $owner = 'test-owner';
        $timeout = 100;
        $token = 'test-token';

        $this->insertTestRow($path, $owner, $timeout, $token);

        $locks = $this->locksBackend->getLocks($path, returnChildLocks: false);

        $this->assertCount(1, $locks);
        $lock = $locks[0];

        $this->assertInstanceOf(LockInfo::class, $lock);

        $this->assertEquals($path, $lock->uri);
        $this->assertEquals($owner, $lock->owner);
        $this->assertEquals($timeout, $lock->timeout);
        $this->assertEquals($token, $lock->token);
    }

    public function testGetLocksReturnsLocksIncludingAncestorLocks(): void {
        $pathA = 'a';
        $pathB = 'a/b';
        $pathC = 'a/b/c';

        $this->insertTestRow($pathA);
        $this->insertTestRow($pathB);
        $this->insertTestRow($pathC);

        $locks = $this->locksBackend->getLocks($pathC, false);

        $this->assertCount(3, $locks);
    }

    public function testGetLocksReturnsEmptyArrayIfNoLocksExistForPath(): void {
        $this->insertTestRow('test2');

        $this->assertEmpty(
            $this->locksBackend->getLocks('test', returnChildLocks: false)
        );
    }

    public function testGetLocksReturnsNoChildLocks(): void {
        $path = 'test';
        $pathA = 'test/a';
        $pathB = 'test/b';

        $this->insertTestRow($pathA);
        $this->insertTestRow($pathB);

        $locks = $this->locksBackend->getLocks($path, returnChildLocks: false);

        $this->assertCount(0, $locks);
    }

    public function testGetLocksReturnsAllChildLocksIfRequested(): void {
        $path = 'test';
        $pathA = 'test/a';
        $pathB = 'test/b';

        $this->insertTestRow($pathA);
        $this->insertTestRow($pathB);

        $locks = $this->locksBackend->getLocks($path, returnChildLocks: true);

        $this->assertCount(2, $locks);
    }

    public function testGetLocksDoesNotReturnExpiredLocks(): void {
        $path = 'test';
        $expiredPath = 'test/a';
        $normalPath = 'test/b';

        $this->insertTestRow($expiredPath, timeout: -1);
        $this->insertTestRow($normalPath);

        $locks = $this->locksBackend->getLocks($path, returnChildLocks: true);

        $this->assertCount(1, $locks);

        $this->assertEquals($normalPath, $locks[0]->uri);
    }

    public function testGetLocksDoesNotReturnLocksOfOtherUser(): void {
        $path = 'test';
        $userPath = 'test/b';
        $otherPath = 'test/a';

        $otherUser = $this->createUser();

        $this->insertTestRow($otherPath, userId: $otherUser->id);
        $this->insertTestRow($userPath);

        $locks = $this->locksBackend->getLocks($path, returnChildLocks: true);

        $this->assertCount(1, $locks);

        $this->assertEquals($userPath, $locks[0]->uri);
    }

    public function testLockInsertsNewLockEntry(): void {
        $this->assertDatabaseEmpty($this->locksBackend::TABLE_NAME);

        $lockInfo = new LockInfo();

        $lockInfo->uri = 'test';
        $lockInfo->owner = 'test-owner';
        $lockInfo->scope = LockInfo::TIMEOUT_INFINITE;
        $lockInfo->depth = 12;
        $lockInfo->token = 'test-token';

        // will be overwritten
        $lockInfo->timeout = 100;
        $lockInfo->created = time() - 1000;

        $result = $this->locksBackend->lock($lockInfo->uri, $lockInfo);

        $this->assertTrue($result);

        $this->assertDatabaseHas($this->locksBackend::TABLE_NAME, [
            'user_id' => $this->user->id,
            'uri' => $lockInfo->uri,
            'owner' => $lockInfo->owner,
            'scope' => $lockInfo->scope,
            'depth' => $lockInfo->depth,
            'token' => $lockInfo->token,
            'timeout' => $this->locksBackend::TIMEOUT,
        ]);

        // no entries with these values, since they are overwritten
        $this->assertDatabaseMissing($this->locksBackend::TABLE_NAME, [
            'timeout' => $lockInfo->timeout,
            'created' => $lockInfo->created,
        ]);
    }

    public function testLockUpdatesExistingLock(): void {
        $path = 'test';
        $token = $this->insertTestRow($path, timeout: 100);

        $lockInfo = new LockInfo();

        $lockInfo->uri = $path;
        $lockInfo->owner = 'test-owner';
        $lockInfo->scope = LockInfo::TIMEOUT_INFINITE;
        $lockInfo->depth = -1;
        $lockInfo->token = $token;

        $result = $this->locksBackend->lock($lockInfo->uri, $lockInfo);

        $this->assertTrue($result);

        $this->assertDatabaseHas($this->locksBackend::TABLE_NAME, [
            'user_id' => $this->user->id,
            'uri' => $lockInfo->uri,
            'owner' => $lockInfo->owner,
            'scope' => $lockInfo->scope,
            'depth' => $lockInfo->depth,
            'token' => $lockInfo->token,
            'timeout' => $this->locksBackend::TIMEOUT,
        ]);

        $this->assertDatabaseCount($this->locksBackend::TABLE_NAME, 1);
    }

    public function testLockDoesNotUpdateLockOfOtherUser(): void {
        $otherUser = $this->createUser();

        $path = 'test';
        $token = $this->insertTestRow($path, userId: $otherUser->id);

        $lockInfo = new LockInfo();

        $lockInfo->uri = $path;
        $lockInfo->owner = 'test-owner';
        $lockInfo->scope = LockInfo::TIMEOUT_INFINITE;
        $lockInfo->depth = -1;
        $lockInfo->token = $token;

        $result = $this->locksBackend->lock($lockInfo->uri, $lockInfo);

        $this->assertTrue($result);

        $this->assertDatabaseCount($this->locksBackend::TABLE_NAME, 2);
    }

    public function testLockReturnsFalseWhenEntryCantBeCreated(): void {
        /**
         * @var Builder|MockInterface
         */
        $testQuery = Mockery::mock(Builder::class);

        $testQuery
            ->shouldReceive('upsert')
            ->once()
            ->andReturn(0);

        $locksBackend = new LocksBackendTestClass(
            $this->authBackend,
            $testQuery
        );

        $lockInfo = new LockInfo();

        $lockInfo->uri = 'path';
        $lockInfo->owner = 'test-owner';
        $lockInfo->scope = LockInfo::TIMEOUT_INFINITE;
        $lockInfo->depth = -1;
        $lockInfo->token = 'test-token';

        $result = $locksBackend->lock($lockInfo->uri, $lockInfo);

        $this->assertFalse($result);
    }

    public function testUnlockDeletesTheLockEntry(): void {
        $path = 'test';
        $token = $this->insertTestRow($path);

        $lockInfo = new LockInfo();
        $lockInfo->token = $token;

        $result = $this->locksBackend->unlock($path, $lockInfo);

        $this->assertTrue($result);

        $this->assertDatabaseMissing($this->locksBackend::TABLE_NAME, [
            'uri' => $path,
            'token' => $token,
            'user_id' => $this->user->id,
        ]);
    }

    public function testUnlockDoesNotDeleteEntryOfOtherUser(): void {
        $otherUser = $this->createUser();

        $path = 'test';
        $token = $this->insertTestRow($path, userId: $otherUser->id);

        $lockInfo = new LockInfo();
        $lockInfo->token = $token;

        $result = $this->locksBackend->unlock($path, $lockInfo);

        $this->assertFalse($result);

        $this->assertDatabaseHas($this->locksBackend::TABLE_NAME, [
            'uri' => $path,
            'token' => $token,
            'user_id' => $otherUser->id,
        ]);
    }

    public function testQueryReturnsQueryBuilderForTable(): void {
        $query = $this->locksBackend->query();

        $this->assertInstanceOf(Builder::class, $query);
        $this->assertEquals($this->locksBackend::TABLE_NAME, $query->from);
    }

    protected function insertTestRow(
        string $uri,
        string $owner = 'test-owner',
        int $timeout = 3000,
        ?string $token = null,
        ?int $userId = null
    ): string {
        $token = $token ?? fake()->password();

        $this->locksBackend->query()->insert([
            'user_id' => $userId ?? $this->user->id,
            'uri' => $uri,
            'depth' => -1, // depth seems to be always infinity
            'timeout' => $timeout,
            'token' => $token,
            'owner' => $owner,
            'created' => time(),
            'scope' => LockInfo::EXCLUSIVE,
        ]);

        return $token;
    }
}

class LocksBackendTestClass extends LocksBackend {
    public function __construct(
        AuthBackend $authBackend,
        protected ?Builder $testQuery = null
    ) {
        parent::__construct($authBackend);
    }

    public function query(): Builder {
        if ($this->testQuery) {
            return $this->testQuery;
        }

        return parent::query();
    }
}
