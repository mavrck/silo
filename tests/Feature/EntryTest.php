<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Entry;
use App\Models\Feed;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_entries_index_is_displayed(): void
    {
        $user = User::factory()->create();
        $feed = Feed::factory()->for($user)->create();
        Entry::factory()->for($feed)->create();

        $response = $this->actingAs($user)->get('/entries');

        $response->assertOk();
    }

    public function test_index_only_shows_the_users_own_entries(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $ownFeed = Feed::factory()->for($user)->create();
        $strangerFeed = Feed::factory()->for($stranger)->create();
        $ownEntry = Entry::factory()->for($ownFeed)->create(['title' => 'Mine']);
        Entry::factory()->for($strangerFeed)->create(['title' => 'Not mine']);

        $response = $this->actingAs($user)->get('/entries');

        $response->assertInertia(fn ($page) => $page
            ->component('Entries/Index')
            ->has('entries.data', 1)
            ->where('entries.data.0.id', $ownEntry->id)
        );
    }

    public function test_index_can_filter_to_unread_only(): void
    {
        $user = User::factory()->create();
        $feed = Feed::factory()->for($user)->create();
        Entry::factory()->for($feed)->read()->create();
        $unread = Entry::factory()->for($feed)->create();

        $response = $this->actingAs($user)->get('/entries?unread=1');

        $response->assertInertia(fn ($page) => $page
            ->has('entries.data', 1)
            ->where('entries.data.0.id', $unread->id)
        );
    }

    public function test_index_can_filter_by_feed(): void
    {
        $user = User::factory()->create();
        $feedA = Feed::factory()->for($user)->create();
        $feedB = Feed::factory()->for($user)->create();
        $entryA = Entry::factory()->for($feedA)->create();
        Entry::factory()->for($feedB)->create();

        $response = $this->actingAs($user)->get("/entries?feed_id={$feedA->id}");

        $response->assertInertia(fn ($page) => $page
            ->has('entries.data', 1)
            ->where('entries.data.0.id', $entryA->id)
        );
    }

    public function test_index_can_filter_by_category(): void
    {
        $user = User::factory()->create();
        $categoryA = Category::factory()->for($user)->create();
        $categoryB = Category::factory()->for($user)->create();
        $feedA = Feed::factory()->for($user)->create(['category_id' => $categoryA->id]);
        $feedB = Feed::factory()->for($user)->create(['category_id' => $categoryB->id]);
        $entryA = Entry::factory()->for($feedA)->create();
        Entry::factory()->for($feedB)->create();

        $response = $this->actingAs($user)->get("/entries?category_id={$categoryA->id}");

        $response->assertInertia(fn ($page) => $page
            ->has('entries.data', 1)
            ->where('entries.data.0.id', $entryA->id)
        );
    }

    public function test_index_can_filter_by_tag(): void
    {
        $user = User::factory()->create();
        $feed = Feed::factory()->for($user)->create();
        $tag = Tag::factory()->for($user)->create();
        $tagged = Entry::factory()->for($feed)->create();
        $tagged->tags()->attach($tag);
        Entry::factory()->for($feed)->create();

        $response = $this->actingAs($user)->get("/entries?tag_id={$tag->id}");

        $response->assertInertia(fn ($page) => $page
            ->has('entries.data', 1)
            ->where('entries.data.0.id', $tagged->id)
        );
    }

    public function test_viewing_an_entry_marks_it_read(): void
    {
        $user = User::factory()->create();
        $feed = Feed::factory()->for($user)->create();
        $entry = Entry::factory()->for($feed)->create();

        $response = $this->actingAs($user)->get("/entries/{$entry->id}");

        $response->assertOk();
        $this->assertTrue($entry->fresh()->is_read);
        $this->assertNotNull($entry->fresh()->read_at);
    }

    public function test_user_can_mark_an_entry_unread(): void
    {
        $user = User::factory()->create();
        $feed = Feed::factory()->for($user)->create();
        $entry = Entry::factory()->for($feed)->read()->create();

        $response = $this->actingAs($user)->patch("/entries/{$entry->id}/unread");

        $response->assertRedirect();
        $this->assertFalse($entry->fresh()->is_read);
        $this->assertNull($entry->fresh()->read_at);
    }

    public function test_user_can_toggle_star_on_an_entry(): void
    {
        $user = User::factory()->create();
        $feed = Feed::factory()->for($user)->create();
        $entry = Entry::factory()->for($feed)->create();

        $this->actingAs($user)->patch("/entries/{$entry->id}/star");
        $this->assertTrue($entry->fresh()->is_starred);

        $this->actingAs($user)->patch("/entries/{$entry->id}/star");
        $this->assertFalse($entry->fresh()->is_starred);
    }

    public function test_user_cannot_view_another_users_entry(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $feed = Feed::factory()->for($owner)->create();
        $entry = Entry::factory()->for($feed)->create();

        $response = $this->actingAs($intruder)->get("/entries/{$entry->id}");

        $response->assertForbidden();
    }

    public function test_user_cannot_change_state_on_another_users_entry(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $feed = Feed::factory()->for($owner)->create();
        $entry = Entry::factory()->for($feed)->create();

        $response = $this->actingAs($intruder)->patch("/entries/{$entry->id}/star");

        $response->assertForbidden();
        $this->assertFalse($entry->fresh()->is_starred);
    }

    public function test_guests_cannot_access_entries(): void
    {
        $response = $this->get('/entries');

        $response->assertRedirect('/login');
    }
}
