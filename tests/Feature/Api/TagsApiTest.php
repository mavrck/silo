<?php

namespace Tests\Feature\Api;

use App\Models\Entry;
use App\Models\Feed;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spectator\Spectator;
use Tests\TestCase;

class TagsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Spectator::using('openapi.yaml');
    }

    public function test_it_requires_authentication(): void
    {
        $response = $this->getJson('/api/tags');

        $response->assertValidRequest();
        $response->assertValidResponse(401);
    }

    public function test_it_lists_only_the_authenticated_users_tags_with_entry_counts(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->for($user)->create();
        $entry = Entry::factory()->for(Feed::factory()->for($user)->create())->create();
        $entry->tags()->attach($tag);

        Tag::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/tags');

        $response->assertValidRequest();
        $response->assertValidResponse(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', (string) $tag->id);
        $response->assertJsonPath('data.0.attributes.entries_count', 1);
    }

    public function test_it_shows_a_single_tag(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->for($user)->create();

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/tags/{$tag->id}");

        $response->assertValidRequest();
        $response->assertValidResponse(200);
        $response->assertJsonPath('data.attributes.name', $tag->name);
    }

    public function test_it_forbids_viewing_another_users_tag(): void
    {
        $user = User::factory()->create();
        $otherUsersTag = Tag::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/tags/{$otherUsersTag->id}");

        $response->assertValidRequest();
        $response->assertValidResponse(403);
    }

    public function test_it_returns_404_for_a_missing_tag(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/tags/999999');

        $response->assertValidRequest();
        $response->assertValidResponse(404);
    }
}
