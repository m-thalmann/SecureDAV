<?php

namespace Tests\Feature;

use App\Models\AccessGroupUser;
use App\Models\Directory;
use App\Models\File;
use App\Models\FileVersion;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

use function Sabre\HTTP\encodePath as sabreEncodePath;

class WebDavTest extends TestCase {
    use LazilyRefreshDatabase;

    protected AccessGroupUser $user;

    protected function setUp(): void {
        parent::setUp();

        $this->user = AccessGroupUser::factory()->create();
    }

    public function testCorsRequestIsHandledIfCorsIsEnabled(): void {
        config([
            'webdav.cors.enabled' => true,
            'webdav.cors.allowed_origins' => ['*'],
        ]);

        $response = $this->options(route('webdav.default'));

        $response->assertNoContent();

        $response->assertHeader('Access-Control-Allow-Origin', '*');
    }

    public function testCorsRequestIsNotHandledIfCorsIsDisabled(): void {
        config([
            'webdav.cors.enabled' => false,
            'webdav.cors.allowed_origins' => ['*'],
        ]);

        $response = $this->options(route('webdav.default'));

        $response->assertNoContent();

        $response->assertHeaderMissing('Access-Control-Allow-Origin');
    }

    /**
     * @dataProvider webdavRouteProvider
     */
    public function testAccessFailsWithNoAuthentication(
        string $routeName
    ): void {
        $response = $this->get(route($routeName));

        $response->assertUnauthorized();

        $response->assertSee('Sabre\DAV\Exception\NotAuthenticated');
    }

    /**
     * @dataProvider webdavRouteProvider
     */
    public function testAccessFailsWithInvalidAuthentication(
        string $routeName
    ): void {
        $response = $this->get(route($routeName), [
            'Authorization' => 'Basic not-user:wrong-password',
        ]);

        $response->assertUnauthorized();

        $response->assertSee('Sabre\DAV\Exception\NotAuthenticated');
    }

    /**
     * @dataProvider webdavRouteProvider
     */
    public function testAccessFailsWhenGroupNotActive(string $routeName): void {
        $this->user->accessGroup->update(['active' => false]);

        $response = $this->fetchWebDav(route($routeName));

        $response->assertUnauthorized();

        $response->assertSee('Sabre\DAV\Exception\NotAuthenticated');
    }

    public function testFilesContainsAccessibleFileWithVersionForUser(): void {
        $file = File::factory()
            ->for($this->user->accessGroup->user)
            ->has(FileVersion::factory(), 'versions')
            ->hasAttached($this->user->accessGroup)
            ->create();

        $response = $this->fetchWebDav(
            route('webdav.files', [
                'uuid' => $file->uuid,
                'name' => $file->name,
            ])
        );

        $response->assertOk();

        $expectedContent = Storage::disk('files')->get(
            $file->latestVersion->storage_path
        );

        /**
         * @var StreamedResponse
         */
        $streamedResponse = $response->baseResponse;

        $content = $this->getStreamedResponseContent($streamedResponse);

        $this->assertEquals($expectedContent, $content);
    }

    public function testFilesDoesNotContainAccessibleFileWithNoVersionForUser(): void {
        $file = File::factory()
            ->for($this->user->accessGroup->user)
            ->hasAttached($this->user->accessGroup)
            ->create();

        $response = $this->fetchWebDav(
            route('webdav.files', [
                'uuid' => $file->uuid,
                'name' => $file->name,
            ])
        );

        $response->assertNotFound();

        $response->assertSee('Sabre\DAV\Exception\NotFound');
    }

    public function testFilesDoesNotContainNonAccessibleFileForUser(): void {
        $file = File::factory()
            ->for($this->user->accessGroup->user)
            ->has(FileVersion::factory(), 'versions')
            ->create();

        $response = $this->fetchWebDav(
            route('webdav.files', [
                'uuid' => $file->uuid,
                'name' => $file->name,
            ])
        );

        $response->assertNotFound();

        $response->assertSee('Sabre\DAV\Exception\NotFound');
    }

    public function testDirectoriesShowsAccessibleFilesWithVersionsAndDirectoriesInBaseDirectory(): void {
        $files = File::factory(5)
            ->for($this->user->accessGroup->user)
            ->has(FileVersion::factory(), 'versions')
            ->hasAttached($this->user->accessGroup)
            ->create(['directory_id' => null]);

        $directories = Directory::factory(3)
            ->for($this->user->accessGroup->user)
            ->create(['parent_directory_id' => null]);

        $response = $this->fetchWebDav(route('webdav.directories'), 'PROPFIND');

        $response->assertStatus(207);

        foreach ($files as $file) {
            $response->assertSee(sabreEncodePath($file->name));
        }

        foreach ($directories as $directory) {
            $response->assertSee(sabreEncodePath($directory->name));
        }
    }

    public function testDirectoriesShowsAccessibleFilesWithVersionsAndDirectoriesInDirectory(): void {
        $directory = Directory::factory()
            ->for($this->user->accessGroup->user)
            ->create();

        $files = File::factory(5)
            ->for($this->user->accessGroup->user)
            ->has(FileVersion::factory(), 'versions')
            ->hasAttached($this->user->accessGroup)
            ->create(['directory_id' => $directory->id]);

        $directories = Directory::factory(3)
            ->for($this->user->accessGroup->user)
            ->create(['parent_directory_id' => $directory->id]);

        $response = $this->fetchWebDav(
            route('webdav.directories', [
                'path' => collect($directory->breadcrumbs)
                    ->map(fn(Directory $directory) => $directory->name)
                    ->join('/'),
            ]),
            'PROPFIND'
        );

        $response->assertStatus(207);

        foreach ($files as $file) {
            $response->assertSee(sabreEncodePath($file->name));
        }

        foreach ($directories as $directory) {
            $response->assertSee(sabreEncodePath($directory->name));
        }
    }

    public function testDirectoriesDoesNotContainDirectoriesOfOtherUsers(): void {
        $otherUser = $this->createUser();

        $directory = Directory::factory()
            ->for($otherUser)
            ->create();

        $response = $this->fetchWebDav(route('webdav.directories'), 'PROPFIND');

        $response->assertDontSee(sabreEncodePath($directory->name));
    }

    public function testDirectoriesDoesNotContainFilesWithoutVersions(): void {
        $file = File::factory()
            ->for($this->user->accessGroup->user)
            ->hasAttached($this->user->accessGroup)
            ->create(['directory_id' => null]);

        $response = $this->fetchWebDav(route('webdav.directories'), 'PROPFIND');

        $response->assertDontSee(sabreEncodePath($file->name));
    }

    public function testDirectoriesDoesNotContainNonAccessibleFiles(): void {
        $file = File::factory()
            ->for($this->user->accessGroup->user)
            ->has(FileVersion::factory(), 'versions')
            ->create(['directory_id' => null]);

        $response = $this->fetchWebDav(route('webdav.directories'), 'PROPFIND');

        $response->assertDontSee(sabreEncodePath($file->name));
    }

    public function testDirectoriesShowsFile(): void {
        $file = File::factory()
            ->for($this->user->accessGroup->user)
            ->has(FileVersion::factory(), 'versions')
            ->hasAttached($this->user->accessGroup)
            ->create(['directory_id' => null]);

        $response = $this->fetchWebDav(
            route('webdav.directories', [
                'path' => $file->name,
            ])
        );

        $response->assertOk();

        $expectedContent = Storage::disk('files')->get(
            $file->latestVersion->storage_path
        );

        /**
         * @var StreamedResponse
         */
        $streamedResponse = $response->baseResponse;

        $content = $this->getStreamedResponseContent($streamedResponse);

        $this->assertEquals($expectedContent, $content);
    }

    public function testFileCanBeLockedAndUnlocked(): void {
        $file = File::factory()
            ->for($this->user->accessGroup->user)
            ->has(FileVersion::factory(), 'versions')
            ->hasAttached($this->user->accessGroup)
            ->create(['directory_id' => null]);

        $lockRequest = <<<XML
<?xml version="1.0" encoding="utf-8" ?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope>
        <D:exclusive />
    </D:lockscope>
    <D:locktype>
        <D:write />
    </D:locktype>
    <D:owner>test-owner</D:owner>
</D:lockinfo>
XML;

        $response = $this->fetchWebDav(
            route('webdav.directories', [
                'path' => $file->name,
            ]),
            'LOCK',
            data: $lockRequest
        );

        $response->assertStatus(200);

        $response->assertHeader('Lock-Token');

        $lockToken = $response->baseResponse->headers->get('Lock-Token');

        $response = $this->fetchWebDav(
            route('webdav.directories', [
                'path' => $file->name,
            ]),
            'UNLOCK',
            headers: [
                'Lock-Token' => $lockToken,
            ]
        );

        $response->assertStatus(204);
    }

    public function testFileOfOtherUserCantBeLocked(): void {
        $otherUser = $this->createUser();

        $file = File::factory()
            ->for($otherUser)
            ->has(FileVersion::factory(), 'versions')
            ->create(['directory_id' => null]);

        $lockRequest = <<<XML
<?xml version="1.0" encoding="utf-8" ?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope>
        <D:exclusive />
    </D:lockscope>
    <D:locktype>
        <D:write />
    </D:locktype>
    <D:owner>test-owner</D:owner>
</D:lockinfo>
XML;

        $response = $this->fetchWebDav(
            route('webdav.directories', [
                'path' => $file->name,
            ]),
            'LOCK',
            data: $lockRequest
        );

        $response->assertStatus(403);
    }

    protected function fetchWebDav(
        string $path,
        string $method = 'GET',
        array $headers = [],
        ?string $data = null
    ): TestResponse {
        $server = $this->transformHeadersToServerVars([
            'Authorization' =>
                'Basic ' . base64_encode($this->user->username . ':password'),
            ...$headers,
        ]);

        return $this->call($method, $path, content: $data, server: $server);
    }

    public static function webdavRouteProvider(): array {
        return [['webdav.files.base'], ['webdav.directories']];
    }
}
