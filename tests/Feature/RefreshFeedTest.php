<?php

namespace Tests\Feature;

use App\Jobs\RefreshFeed;
use App\Jobs\SummarizeEntry;
use App\Jobs\TranslateEntry;
use App\Models\Feed;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\Concerns\FakesFeedIo;
use Tests\TestCase;

class RefreshFeedTest extends TestCase
{
    use FakesFeedIo, RefreshDatabase;

    public function test_it_creates_entries_from_a_feed(): void
    {
        $feed = Feed::factory()->create(['title' => 'Existing Title']);
        $this->fakeFeedIo([file_get_contents(__DIR__.'/../Fixtures/sample-feed.xml')]);

        RefreshFeed::dispatchSync($feed);

        $this->assertDatabaseCount('entries', 1);
        $this->assertDatabaseHas('entries', [
            'feed_id' => $feed->id,
            'guid' => 'https://example.test/first-post',
            'title' => 'First post',
            'author' => 'Ada Lovelace',
        ]);

        $feed->refresh();
        $this->assertNotNull($feed->last_fetched_at);
        $this->assertNull($feed->last_fetch_error);
        // A user-supplied title is preserved rather than overwritten by the feed's own title.
        $this->assertSame('Existing Title', $feed->title);
    }

    public function test_it_parses_a_plain_atom_feed(): void
    {
        $feed = Feed::factory()->create();
        $this->fakeFeedIo([file_get_contents(__DIR__.'/../Fixtures/sample-atom-feed.xml')]);

        RefreshFeed::dispatchSync($feed);

        $this->assertDatabaseCount('entries', 1);
        $this->assertDatabaseHas('entries', [
            'feed_id' => $feed->id,
            'guid' => 'https://example.test/first-post',
            'title' => 'First Atom post',
            'author' => 'Ada Lovelace',
            'url' => 'https://example.test/first-post',
        ]);

        $entry = $feed->entries()->sole();
        // The sanitizer HTML-entity-encodes the apostrophe on the way out.
        $this->assertStringContainsString('The first post', $entry->content);
        $this->assertStringContainsString('content.', $entry->content);

        $feed->refresh();
        $this->assertNull($feed->last_fetch_error);
    }

    public function test_it_does_not_duplicate_entries_on_repeated_fetches(): void
    {
        $feed = Feed::factory()->create();

        $this->fakeFeedIo([file_get_contents(__DIR__.'/../Fixtures/sample-feed.xml')]);
        RefreshFeed::dispatchSync($feed);

        $this->fakeFeedIo([file_get_contents(__DIR__.'/../Fixtures/sample-feed-updated.xml')]);
        RefreshFeed::dispatchSync($feed->refresh());

        $this->assertDatabaseCount('entries', 2);
    }

    public function test_it_does_not_resurrect_a_deleted_entry_on_a_later_refresh(): void
    {
        $feed = Feed::factory()->create();

        $this->fakeFeedIo([file_get_contents(__DIR__.'/../Fixtures/sample-feed.xml')]);
        RefreshFeed::dispatchSync($feed);

        $entry = $feed->entries()->sole();
        $entry->delete();

        $this->fakeFeedIo([file_get_contents(__DIR__.'/../Fixtures/sample-feed.xml')]);
        RefreshFeed::dispatchSync($feed->refresh());

        $this->assertDatabaseCount('entries', 1);
        $this->assertSoftDeleted('entries', ['id' => $entry->id]);
    }

    public function test_it_can_refresh_a_feed_again_after_the_server_sent_a_last_modified_header(): void
    {
        // Regression test: a prior bug passed the feed's last_modified_at as
        // FeedIo::read()'s second argument (a ?FeedInterface), rather than its
        // third (?DateTime). Every other test's fake responses omit
        // Last-Modified, so last_modified_at stayed null and the misplaced
        // argument was accepted silently — only a feed whose server actually
        // sends the header (like the real one this was caught against) hits
        // the type error on the second fetch.
        $feed = Feed::factory()->create();

        $this->fakeFeedIo([new Response(200, [
            'Last-Modified' => 'Mon, 06 Jan 2026 09:00:00 GMT',
        ], file_get_contents(__DIR__.'/../Fixtures/sample-feed.xml'))]);
        RefreshFeed::dispatchSync($feed);
        $feed->refresh();
        $this->assertNotNull($feed->last_modified_at);

        // A non-null modifiedSince makes the real HTTP client issue a
        // conditional HEAD check before the GET, so two responses are queued.
        $updatedHeaders = ['Last-Modified' => 'Tue, 07 Jan 2026 09:00:00 GMT'];
        $this->fakeFeedIo([
            new Response(200, $updatedHeaders, ''),
            new Response(200, $updatedHeaders, file_get_contents(__DIR__.'/../Fixtures/sample-feed-updated.xml')),
        ]);
        RefreshFeed::dispatchSync($feed);

        $feed->refresh();
        $this->assertNull($feed->last_fetch_error);
        $this->assertDatabaseCount('entries', 2);
    }

    public function test_it_records_an_error_when_the_feed_cannot_be_read(): void
    {
        $feed = Feed::factory()->create();
        $this->fakeFeedIo([new Response(404)]);

        RefreshFeed::dispatchSync($feed);

        $feed->refresh();
        $this->assertNotNull($feed->last_fetched_at);
        $this->assertNotNull($feed->last_fetch_error);
        $this->assertDatabaseCount('entries', 0);
    }

    public function test_it_strips_dangerous_html_from_entry_content(): void
    {
        $feed = Feed::factory()->create();
        $this->fakeFeedIo([file_get_contents(__DIR__.'/../Fixtures/sample-feed-malicious.xml')]);

        RefreshFeed::dispatchSync($feed);

        $entry = $feed->entries()->sole();
        $this->assertStringContainsString('<p>Safe text</p>', $entry->content);
        $this->assertStringNotContainsString('<script', $entry->content);
        $this->assertStringNotContainsString('onerror', $entry->content);
    }

    public function test_it_does_not_queue_summarization_when_the_feed_has_it_disabled(): void
    {
        Bus::fake([SummarizeEntry::class]);
        $feed = Feed::factory()->create(['summarize' => false]);
        $this->fakeFeedIo([file_get_contents(__DIR__.'/../Fixtures/sample-feed.xml')]);

        RefreshFeed::dispatchSync($feed);

        Bus::assertNotDispatched(SummarizeEntry::class);
    }

    public function test_it_queues_summarization_for_new_entries_when_the_feed_has_it_enabled(): void
    {
        Bus::fake([SummarizeEntry::class]);
        $feed = Feed::factory()->create(['summarize' => true]);
        $this->fakeFeedIo([file_get_contents(__DIR__.'/../Fixtures/sample-feed.xml')]);

        RefreshFeed::dispatchSync($feed);

        Bus::assertDispatchedTimes(SummarizeEntry::class, 1);
    }

    public function test_it_does_not_requeue_summarization_for_an_entry_that_already_existed(): void
    {
        $feed = Feed::factory()->create(['summarize' => true]);

        $this->fakeFeedIo([file_get_contents(__DIR__.'/../Fixtures/sample-feed.xml')]);
        RefreshFeed::dispatchSync($feed);

        Bus::fake([SummarizeEntry::class]);
        $this->fakeFeedIo([file_get_contents(__DIR__.'/../Fixtures/sample-feed-updated.xml')]);
        RefreshFeed::dispatchSync($feed->refresh());

        // Only the second (newly added) entry should be queued for summarization.
        Bus::assertDispatchedTimes(SummarizeEntry::class, 1);
    }

    public function test_it_does_not_queue_translation_when_the_feed_has_no_target_language(): void
    {
        Bus::fake([TranslateEntry::class]);
        $feed = Feed::factory()->create(['translate_to' => null]);
        $this->fakeFeedIo([file_get_contents(__DIR__.'/../Fixtures/sample-feed.xml')]);

        RefreshFeed::dispatchSync($feed);

        Bus::assertNotDispatched(TranslateEntry::class);
    }

    public function test_it_queues_translation_for_new_entries_when_the_feed_has_a_target_language(): void
    {
        Bus::fake([TranslateEntry::class]);
        $feed = Feed::factory()->create(['translate_to' => 'es']);
        $this->fakeFeedIo([file_get_contents(__DIR__.'/../Fixtures/sample-feed.xml')]);

        RefreshFeed::dispatchSync($feed);

        Bus::assertDispatchedTimes(TranslateEntry::class, 1);
    }

    public function test_it_does_not_requeue_translation_for_an_entry_that_already_existed(): void
    {
        $feed = Feed::factory()->create(['translate_to' => 'es']);

        $this->fakeFeedIo([file_get_contents(__DIR__.'/../Fixtures/sample-feed.xml')]);
        RefreshFeed::dispatchSync($feed);

        Bus::fake([TranslateEntry::class]);
        $this->fakeFeedIo([file_get_contents(__DIR__.'/../Fixtures/sample-feed-updated.xml')]);
        RefreshFeed::dispatchSync($feed->refresh());

        // Only the second (newly added) entry should be queued for translation.
        Bus::assertDispatchedTimes(TranslateEntry::class, 1);
    }

    public function test_it_does_not_queue_translation_when_translation_is_globally_disabled(): void
    {
        config(['translation.enabled' => false]);
        Bus::fake([TranslateEntry::class]);
        $feed = Feed::factory()->create(['translate_to' => 'es']);
        $this->fakeFeedIo([file_get_contents(__DIR__.'/../Fixtures/sample-feed.xml')]);

        RefreshFeed::dispatchSync($feed);

        Bus::assertNotDispatched(TranslateEntry::class);
    }
}
