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

    public function test_marking_unread_from_the_show_page_survives_the_redirect(): void
    {
        // Regression test: markUnread() used to redirect back(), which sent
        // the user right back to the entry's own show page — and show()
        // unconditionally marks the entry read on view, instantly undoing
        // the unread action. This reproduces the full browser round trip.
        $user = User::factory()->create();
        $feed = Feed::factory()->for($user)->create();
        $entry = Entry::factory()->for($feed)->create();

        $this->actingAs($user)->get("/entries/{$entry->id}");
        $this->assertTrue($entry->fresh()->is_read);

        $response = $this->actingAs($user)->patch("/entries/{$entry->id}/unread");
        $response->assertRedirect(route('entries.index'));

        $this->actingAs($user)->get($response->headers->get('Location'));

        $this->assertFalse($entry->fresh()->is_read);
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

    public function test_index_reports_the_unread_count_for_the_current_filters(): void
    {
        $user = User::factory()->create();
        $feedA = Feed::factory()->for($user)->create();
        $feedB = Feed::factory()->for($user)->create();
        Entry::factory()->for($feedA)->create();
        Entry::factory()->for($feedA)->read()->create();
        Entry::factory()->for($feedB)->create();

        $response = $this->actingAs($user)->get("/entries?feed_id={$feedA->id}");

        $response->assertInertia(fn ($page) => $page->where('unreadCount', 1));
    }

    public function test_mark_all_read_marks_unread_entries_matching_the_current_filters(): void
    {
        $user = User::factory()->create();
        $feedA = Feed::factory()->for($user)->create();
        $feedB = Feed::factory()->for($user)->create();
        $unreadInFeedA = Entry::factory()->for($feedA)->create();
        $alreadyReadInFeedA = Entry::factory()->for($feedA)->read()->create();
        $unreadInFeedB = Entry::factory()->for($feedB)->create();

        $response = $this->actingAs($user)->patch('/entries/mark-all-read', ['feed_id' => $feedA->id]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Marked 1 entry as read.');
        $this->assertTrue($unreadInFeedA->fresh()->is_read);
        $this->assertNotNull($unreadInFeedA->fresh()->read_at);
        $this->assertNotNull($alreadyReadInFeedA->fresh()->read_at);
        $this->assertFalse($unreadInFeedB->fresh()->is_read);
    }

    public function test_mark_all_read_respects_category_tag_and_starred_filters(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        $feed = Feed::factory()->for($user)->create(['category_id' => $category->id]);
        $tag = Tag::factory()->for($user)->create();

        $matches = Entry::factory()->for($feed)->create();
        $matches->tags()->attach($tag);
        $matches->toggleStarred();

        $wrongTag = Entry::factory()->for($feed)->create();

        $response = $this->actingAs($user)->patch('/entries/mark-all-read', [
            'category_id' => $category->id,
            'tag_id' => $tag->id,
            'starred' => 1,
        ]);

        $response->assertSessionHas('status', 'Marked 1 entry as read.');
        $this->assertTrue($matches->fresh()->is_read);
        $this->assertFalse($wrongTag->fresh()->is_read);
    }

    public function test_mark_all_read_only_affects_the_authenticated_users_entries(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $strangerFeed = Feed::factory()->for($stranger)->create();
        $strangerEntry = Entry::factory()->for($strangerFeed)->create();

        $this->actingAs($user)->patch('/entries/mark-all-read');

        $this->assertFalse($strangerEntry->fresh()->is_read);
    }

    public function test_mark_all_read_flashes_a_message_when_there_is_nothing_to_mark(): void
    {
        $user = User::factory()->create();
        $feed = Feed::factory()->for($user)->create();
        Entry::factory()->for($feed)->read()->create();

        $response = $this->actingAs($user)->patch('/entries/mark-all-read');

        $response->assertSessionHas('status', 'No unread entries to mark as read.');
    }

    public function test_guests_cannot_mark_all_read(): void
    {
        $response = $this->patch('/entries/mark-all-read');

        $response->assertRedirect('/login');
    }
}
