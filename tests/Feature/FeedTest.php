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
