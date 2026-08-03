<?php

namespace Tests\Feature\Api;

use App\Models\Entry;
use App\Models\Feed;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spectator\Spectator;
use Tests\TestCase;

class EntriesApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Spectator::using('openapi.yaml');
    }

    public function test_it_requires_authentication(): void
    {
        $response = $this->getJson('/api/entries');

        $response->assertValidRequest();
        $response->assertValidResponse(401);
    }

    public function test_it_lists_only_the_authenticated_users_entries(): void
    {
        $user = User::factory()->create();
        $feed = Feed::factory()->for($user)->create();
        $entry = Entry::factory()->for($feed)->create();

        Entry::factory()->for(Feed::factory()->create())->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/entries');

        $response->assertValidRequest();
        $response->assertValidResponse(200);
        $response->assertJsonPath('data.0.type', 'entries');
        $response->assertJsonPath('data.0.id', (string) $entry->id);
        $response->assertJsonPath('data.0.relationships.feed.data.id', (string) $feed->id);
        $response->assertJsonCount(1, 'data');
    }

    public function test_it_filters_entries_by_unread(): void
    {
        $user = User::factory()->create();
        $feed = Feed::factory()->for($user)->create();
        $unread = Entry::factory()->for($feed)->create();
        Entry::factory()->read()->for($feed)->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/entries?unread=1');

        $response->assertValidRequest();
        $response->assertValidResponse(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', (string) $unread->id);
    }

    public function test_it_filters_entries_by_tag(): void
    {
        $user = User::factory()->create();
        $feed = Feed::factory()->for($user)->create();
        $tag = Tag::factory()->for($user)->create();
        $tagged = Entry::factory()->for($feed)->create();
        $tagged->tags()->attach($tag);
        Entry::factory()->for($feed)->create();

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/entries?tag_id={$tag->id}");

        $response->assertValidRequest();
        $response->assertValidResponse(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', (string) $tagged->id);
    }

    public function test_it_shows_a_single_entry_with_feed_and_tags_included(): void
    {
        $user = User::factory()->create();
        $feed = Feed::factory()->for($user)->create();
        $tag = Tag::factory()->for($user)->create();
        $entry = Entry::factory()->for($feed)->create();
        $entry->tags()->attach($tag);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/entries/{$entry->id}");

        $response->assertValidRequest();
        $response->assertValidResponse(200);
        $response->assertJsonPath('data.type', 'entries');
        $response->assertJsonPath('data.relationships.tags.data.0.id', (string) $tag->id);

        $includedTypes = collect($response->json('included'))->pluck('type')->all();
        $this->assertContains('feeds', $includedTypes);
        $this->assertContains('tags', $includedTypes);
    }

    public function test_it_forbids_viewing_another_users_entry(): void
    {
        $user = User::factory()->create();
        $otherUsersEntry = Entry::factory()->for(Feed::factory()->create())->create();

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/entries/{$otherUsersEntry->id}");

        $response->assertValidRequest();
        $response->assertValidResponse(403);
    }

    public function test_it_returns_404_for_a_missing_entry(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/entries/999999');

        $response->assertValidRequest();
        $response->assertValidResponse(404);
    }
}
