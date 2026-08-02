<?php

namespace Tests\Feature;

use App\Models\Entry;
use App\Models\Feed;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    public function test_tags_index_is_displayed(): void
    {
        $user = User::factory()->create();
        Tag::factory()->for($user)->create();

        $response = $this->actingAs($user)->get('/tags');

        $response->assertOk();
    }

    public function test_attaching_a_tag_by_name_creates_it(): void
    {
        $user = User::factory()->create();
        $feed = Feed::factory()->for($user)->create();
        $entry = Entry::factory()->for($feed)->create();

        $response = $this->actingAs($user)->post("/entries/{$entry->id}/tags", [
            'name' => 'reading-list',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tags', ['user_id' => $user->id, 'name' => 'reading-list']);
        $this->assertTrue($entry->fresh()->tags->pluck('name')->contains('reading-list'));
    }

    public function test_attaching_a_tag_twice_reuses_the_existing_tag(): void
    {
        $user = User::factory()->create();
        $feed = Feed::factory()->for($user)->create();
        $entryA = Entry::factory()->for($feed)->create();
        $entryB = Entry::factory()->for($feed)->create();

        $this->actingAs($user)->post("/entries/{$entryA->id}/tags", ['name' => 'reading-list']);
        $this->actingAs($user)->post("/entries/{$entryB->id}/tags", ['name' => 'reading-list']);

        $this->assertDatabaseCount('tags', 1);
    }

    public function test_user_can_detach_a_tag_from_an_entry(): void
    {
        $user = User::factory()->create();
        $feed = Feed::factory()->for($user)->create();
        $entry = Entry::factory()->for($feed)->create();
        $tag = Tag::factory()->for($user)->create();
        $entry->tags()->attach($tag);

        $response = $this->actingAs($user)->delete("/entries/{$entry->id}/tags/{$tag->id}");

        $response->assertRedirect();
        $this->assertFalse($entry->fresh()->tags->contains($tag));
    }

    public function test_user_cannot_tag_another_users_entry(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $feed = Feed::factory()->for($owner)->create();
        $entry = Entry::factory()->for($feed)->create();

        $response = $this->actingAs($intruder)->post("/entries/{$entry->id}/tags", [
            'name' => 'reading-list',
        ]);

        $response->assertForbidden();
    }

    public function test_user_can_rename_their_own_tag(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->for($user)->create(['name' => 'old-name']);

        $response = $this->actingAs($user)->patch("/tags/{$tag->id}", ['name' => 'new-name']);

        $response->assertRedirect();
        $this->assertDatabaseHas('tags', ['id' => $tag->id, 'name' => 'new-name']);
    }

    public function test_user_cannot_rename_another_users_tag(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $tag = Tag::factory()->for($owner)->create(['name' => 'old-name']);

        $response = $this->actingAs($intruder)->patch("/tags/{$tag->id}", ['name' => 'new-name']);

        $response->assertForbidden();
        $this->assertDatabaseHas('tags', ['id' => $tag->id, 'name' => 'old-name']);
    }

    public function test_user_can_delete_their_own_tag(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->for($user)->create();

        $response = $this->actingAs($user)->delete("/tags/{$tag->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }

    public function test_user_cannot_delete_another_users_tag(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $tag = Tag::factory()->for($owner)->create();

        $response = $this->actingAs($intruder)->delete("/tags/{$tag->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('tags', ['id' => $tag->id]);
    }
}
