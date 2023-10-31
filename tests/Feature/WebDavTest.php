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

    protected function fetchWebDav(
        string $path,
        string $method = 'GET'
    ): TestResponse {
        $server = $this->transformHeadersToServerVars([
            'Authorization' =>
                'Basic ' . base64_encode($this->user->username . ':password'),
        ]);

        return $this->call($method, $path, server: $server);
    }

    public static function webdavRouteProvider(): array {
        return [['webdav.files.base'], ['webdav.directories']];
    }
}
