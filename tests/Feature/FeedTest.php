<?php

namespace Tests\Feature;

use App\Jobs\RefreshFeed;
use App\Models\Category;
use App\Models\Feed;
use App\Models\User;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\Concerns\FakesFeedIo;
use Tests\TestCase;

class FeedTest extends TestCase
{
    use FakesFeedIo, RefreshDatabase;

    public function test_feeds_index_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/feeds');

        $response->assertOk();
    }

    public function test_user_can_subscribe_to_a_feed(): void
    {
        Bus::fake();
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        $this->fakeFeedIo([file_get_contents(__DIR__.'/../Fixtures/sample-feed.xml')]);

        $response = $this->actingAs($user)->post('/feeds', [
            'url' => 'https://example.test/feed.xml',
            'category_id' => $category->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('feeds', [
            'user_id' => $user->id,
            'category_id' => $category->id,
            'url' => 'https://example.test/feed.xml',
            'title' => 'Example Feed',
        ]);
        Bus::assertDispatched(RefreshFeed::class);
    }

    public function test_summarize_defaults_to_off_when_subscribing(): void
    {
        Bus::fake();
        $user = User::factory()->create();
        $this->fakeFeedIo([file_get_contents(__DIR__.'/../Fixtures/sample-feed.xml')]);

        $this->actingAs($user)->post('/feeds', [
            'url' => 'https://example.test/feed.xml',
        ]);

        $this->assertDatabaseHas('feeds', [
            'url' => 'https://example.test/feed.xml',
            'summarize' => false,
        ]);
    }

    public function test_user_can_enable_summarize_when_subscribing(): void
    {
        Bus::fake();
        $user = User::factory()->create();
        $this->fakeFeedIo([file_get_contents(__DIR__.'/../Fixtures/sample-feed.xml')]);

        $this->actingAs($user)->post('/feeds', [
            'url' => 'https://example.test/feed.xml',
            'summarize' => true,
        ]);

        $this->assertDatabaseHas('feeds', [
            'url' => 'https://example.test/feed.xml',
            'summarize' => true,
        ]);
    }

    public function test_user_can_toggle_summarize_on_their_own_feed(): void
    {
        $user = User::factory()->create();
        $feed = Feed::factory()->for($user)->create(['summarize' => false]);

        $response = $this->actingAs($user)->patch("/feeds/{$feed->id}/summarize");

        $response->assertRedirect();
        $this->assertTrue($feed->fresh()->summarize);

        $this->actingAs($user)->patch("/feeds/{$feed->id}/summarize");
        $this->assertFalse($feed->fresh()->summarize);
    }

    public function test_user_cannot_toggle_summarize_on_another_users_feed(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $feed = Feed::factory()->for($owner)->create(['summarize' => false]);

        $response = $this->actingAs($intruder)->patch("/feeds/{$feed->id}/summarize");

        $response->assertForbidden();
        $this->assertFalse($feed->fresh()->summarize);
    }

    public function test_user_can_set_a_target_language_when_subscribing(): void
    {
        Bus::fake();
        $user = User::factory()->create();
        $this->fakeFeedIo([file_get_contents(__DIR__.'/../Fixtures/sample-feed.xml')]);

        $this->actingAs($user)->post('/feeds', [
            'url' => 'https://example.test/feed.xml',
            'translate_to' => 'es',
        ]);

        $this->assertDatabaseHas('feeds', [
            'url' => 'https://example.test/feed.xml',
            'translate_to' => 'es',
        ]);
    }

    public function test_translate_to_defaults_to_null_when_subscribing(): void
    {
        Bus::fake();
        $user = User::factory()->create();
        $this->fakeFeedIo([file_get_contents(__DIR__.'/../Fixtures/sample-feed.xml')]);

        $this->actingAs($user)->post('/feeds', [
            'url' => 'https://example.test/feed.xml',
        ]);

        $this->assertDatabaseHas('feeds', [
            'url' => 'https://example.test/feed.xml',
            'translate_to' => null,
        ]);
    }

    public function test_subscribing_with_an_invalid_language_code_fails_validation(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/feeds', [
            'url' => 'https://example.test/feed.xml',
            'translate_to' => 'not-a-real-language',
        ]);

        $response->assertSessionHasErrors('translate_to');
        $this->assertDatabaseCount('feeds', 0);
    }

    public function test_user_can_set_and_clear_the_target_language_on_their_own_feed(): void
    {
        $user = User::factory()->create();
        $feed = Feed::factory()->for($user)->create(['translate_to' => null]);

        $response = $this->actingAs($user)->patch("/feeds/{$feed->id}/translate", [
            'translate_to' => 'fr',
        ]);

        $response->assertRedirect();
        $this->assertSame('fr', $feed->fresh()->translate_to);

        $this->actingAs($user)->patch("/feeds/{$feed->id}/translate", [
            'translate_to' => null,
        ]);
        $this->assertNull($feed->fresh()->translate_to);
    }

    public function test_user_cannot_set_the_target_language_on_another_users_feed(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $feed = Feed::factory()->for($owner)->create(['translate_to' => null]);

        $response = $this->actingAs($intruder)->patch("/feeds/{$feed->id}/translate", [
            'translate_to' => 'fr',
        ]);

        $response->assertForbidden();
        $this->assertNull($feed->fresh()->translate_to);
    }

    public function test_subscribing_without_a_category_creates_an_uncategorized_one(): void
    {
        Bus::fake();
        $user = User::factory()->create();
        $this->fakeFeedIo([file_get_contents(__DIR__.'/../Fixtures/sample-feed.xml')]);

        $this->actingAs($user)->post('/feeds', [
            'url' => 'https://example.test/feed.xml',
        ]);

        $this->assertDatabaseHas('categories', [
            'user_id' => $user->id,
            'name' => 'Uncategorized',
        ]);
    }

    public function test_subscribing_to_an_unreadable_url_fails_validation(): void
    {
        $user = User::factory()->create();
        $this->fakeFeedIo([new Response(404)]);

        $response = $this->actingAs($user)->post('/feeds', [
            'url' => 'https://example.test/not-a-feed',
        ]);

        $response->assertSessionHasErrors('url');
        $this->assertDatabaseCount('feeds', 0);
    }

    public function test_user_cannot_subscribe_to_the_same_url_twice(): void
    {
        $user = User::factory()->create();
        Feed::factory()->for($user)->create(['url' => 'https://example.test/feed.xml']);

        $response = $this->actingAs($user)->post('/feeds', [
            'url' => 'https://example.test/feed.xml',
        ]);

        $response->assertSessionHasErrors('url');
    }

    public function test_user_can_rename_and_recategorize_their_own_feed(): void
    {
        $user = User::factory()->create();
        $oldCategory = Category::factory()->for($user)->create();
        $newCategory = Category::factory()->for($user)->create();
        $feed = Feed::factory()->for($user)->create([
            'category_id' => $oldCategory->id,
            'title' => 'Old Title',
        ]);

        $response = $this->actingAs($user)->patch("/feeds/{$feed->id}", [
            'title' => 'New Title',
            'category_id' => $newCategory->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('feeds', [
            'id' => $feed->id,
            'title' => 'New Title',
            'category_id' => $newCategory->id,
        ]);
    }

    public function test_user_cannot_move_a_feed_into_another_users_category(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $strangerCategory = Category::factory()->for($stranger)->create();
        $feed = Feed::factory()->for($user)->create();

        $response = $this->actingAs($user)->patch("/feeds/{$feed->id}", [
            'title' => 'New Title',
            'category_id' => $strangerCategory->id,
        ]);

        $response->assertSessionHasErrors('category_id');
    }

    public function test_user_cannot_edit_another_users_feed(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $feed = Feed::factory()->for($owner)->create(['title' => 'Original']);

        $response = $this->actingAs($intruder)->patch("/feeds/{$feed->id}", [
            'title' => 'Hijacked',
            'category_id' => Category::factory()->for($owner)->create()->id,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('feeds', ['id' => $feed->id, 'title' => 'Original']);
    }

    public function test_user_can_unsubscribe_from_their_own_feed(): void
    {
        $user = User::factory()->create();
        $feed = Feed::factory()->for($user)->create();

        $response = $this->actingAs($user)->delete("/feeds/{$feed->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('feeds', ['id' => $feed->id]);
    }

    public function test_user_cannot_unsubscribe_another_users_feed(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $feed = Feed::factory()->for($owner)->create();

        $response = $this->actingAs($intruder)->delete("/feeds/{$feed->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('feeds', ['id' => $feed->id]);
    }

    public function test_guests_cannot_access_feeds(): void
    {
        $response = $this->get('/feeds');

        $response->assertRedirect('/login');
    }
}
