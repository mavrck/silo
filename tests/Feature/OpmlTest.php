<?php

namespace Tests\Feature;

use App\Jobs\RefreshFeed;
use App\Models\Category;
use App\Models\Feed;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class OpmlTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_import_an_opml_file(): void
    {
        Bus::fake();
        $user = User::factory()->create();
        $file = UploadedFile::fake()->createWithContent(
            'subscriptions.opml',
            file_get_contents(__DIR__.'/../Fixtures/sample.opml')
        );

        $response = $this->actingAs($user)->post('/opml/import', ['file' => $file]);

        $response->assertRedirect();
        $this->assertDatabaseHas('categories', ['user_id' => $user->id, 'name' => 'Tech']);
        $this->assertDatabaseHas('categories', ['user_id' => $user->id, 'name' => 'Imported']);
        $this->assertDatabaseHas('feeds', [
            'user_id' => $user->id,
            'url' => 'https://example.test/tech.xml',
            'title' => 'Example Feed',
        ]);
        $this->assertDatabaseHas('feeds', [
            'user_id' => $user->id,
            'url' => 'https://example.test/loose.xml',
            'title' => 'Loose Feed',
        ]);
        Bus::assertDispatchedTimes(RefreshFeed::class, 2);
    }

    public function test_importing_skips_feeds_the_user_already_has(): void
    {
        Bus::fake();
        $user = User::factory()->create();
        Feed::factory()->for($user)->create(['url' => 'https://example.test/tech.xml']);
        $file = UploadedFile::fake()->createWithContent(
            'subscriptions.opml',
            file_get_contents(__DIR__.'/../Fixtures/sample.opml')
        );

        $this->actingAs($user)->post('/opml/import', ['file' => $file]);

        $this->assertDatabaseCount('feeds', 2);
        Bus::assertDispatchedTimes(RefreshFeed::class, 1);
    }

    public function test_user_can_export_their_subscriptions_as_opml(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create(['name' => 'Tech']);
        Feed::factory()->for($user)->create([
            'category_id' => $category->id,
            'title' => 'Example Feed',
            'url' => 'https://example.test/tech.xml',
        ]);

        $response = $this->actingAs($user)->get('/opml/export');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/x-opml; charset=UTF-8');
        $response->assertSee('https://example.test/tech.xml', escape: false);
        $response->assertSee('Tech', escape: false);
    }
}
