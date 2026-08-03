<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Feed;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spectator\Spectator;
use Tests\TestCase;

class FeedsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Spectator::using('openapi.yaml');
    }

    public function test_it_requires_authentication(): void
    {
        $response = $this->getJson('/api/feeds');

        $response->assertValidRequest();
        $response->assertValidResponse(401);
    }

    public function test_it_lists_only_the_authenticated_users_feeds(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        $feed = Feed::factory()->for($user)->for($category)->create();

        $otherUsersFeed = Feed::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/feeds');

        $response->assertValidRequest();
        $response->assertValidResponse(200);
        $response->assertJsonPath('data.0.type', 'feeds');
        $response->assertJsonPath('data.0.id', (string) $feed->id);
        $response->assertJsonPath('data.0.relationships.category.data.id', (string) $category->id);
        $response->assertJsonMissing(['id' => (string) $otherUsersFeed->id]);
    }

    public function test_it_shows_a_single_feed_with_its_category_included(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        $feed = Feed::factory()->for($user)->for($category)->create();

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/feeds/{$feed->id}");

        $response->assertValidRequest();
        $response->assertValidResponse(200);
        $response->assertJsonPath('data.type', 'feeds');
        $response->assertJsonPath('data.id', (string) $feed->id);
        $response->assertJsonPath('included.0.type', 'categories');
        $response->assertJsonPath('included.0.id', (string) $category->id);
    }

    public function test_it_forbids_viewing_another_users_feed(): void
    {
        $user = User::factory()->create();
        $otherUsersFeed = Feed::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/feeds/{$otherUsersFeed->id}");

        $response->assertValidRequest();
        $response->assertValidResponse(403);
    }

    public function test_it_returns_404_for_a_missing_feed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/feeds/999999');

        $response->assertValidRequest();
        $response->assertValidResponse(404);
    }
}
