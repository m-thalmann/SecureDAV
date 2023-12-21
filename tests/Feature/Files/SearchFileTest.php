<?php

namespace Tests\Feature\Files;

use App\Models\File;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SearchFileTest extends TestCase {
    use LazilyRefreshDatabase;

    protected User $user;

    protected function setUp(): void {
        parent::setUp();

        $this->user = $this->createUser();
        $this->actingAs($this->user);
    }

    public function testViewCanBeRenderedWithoutQuery(): void {
        $response = $this->get('/files/search');

        $response->assertOk();
    }

    public function testViewCanBeRenderedWithQuery(): void {
        $response = $this->get('/files/search?q=foo');

        $response->assertOk();
    }

    public function testViewWithoutQueryDoesNotSearch(): void {
        $response = $this->get('/files/search');

        $response->assertDontSee('Results');
    }

    public function testViewWithQuerySearches(): void {
        $searchString = 'very-long-test-name';

        $includedFiles = collect();

        $includedFiles->push(
            File::factory()
                ->for($this->user)
                ->create([
                    'name' => "{$searchString}",
                ]),
            File::factory()
                ->for($this->user)
                ->create([
                    'name' => "-{$searchString}_",
                ]),
            File::factory()
                ->for($this->user)
                ->create([
                    'description' => "+{$searchString}?",
                ])
        );

        $excludedFiles = collect();

        $excludedFiles->push(
            File::factory()
                ->for($this->user)
                ->create([
                    'name' => 'not-included-file',
                ]),
            File::factory()
                ->for($this->user)
                ->create([
                    'name' => '-not-matched_',
                ]),
            File::factory()
                ->for($this->user)
                ->create([
                    'name' => 'no-match-for-this-search',
                    'description' => '+not-a-match?',
                ])
        );

        $response = $this->get("/files/search?q={$searchString}");

        $response->assertSee('Results');
        $response->assertSee(count($includedFiles));

        foreach ($includedFiles as $file) {
            $response->assertSee($file->name);
        }

        foreach ($excludedFiles as $file) {
            $response->assertDontSee($file->name);
        }
    }

    public function testViewWithQueryDoesNotIncludeFilesOfOtherUsers(): void {
        $searchString = 'very-long-test-name';

        $files = collect();

        $files->push(
            File::factory()->create([
                'name' => "prefix{$searchString}",
            ]),
            File::factory()->create([
                'description' => $searchString,
            ])
        );

        $response = $this->get("/files/search?q={$searchString}");

        $response->assertSee('Results');
        $response->assertSee(0);

        foreach ($files as $file) {
            $response->assertDontSee($file->name);
        }
    }

    public function testViewWithQueryIncludesSearchInInput(): void {
        $searchString = 'very-long-test-name';

        $response = $this->get("/files/search?q={$searchString}");

        $response->assertSee($searchString);
    }
}
