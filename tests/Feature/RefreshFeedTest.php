<?php

namespace Tests\Feature;

use App\Jobs\RefreshFeed;
use App\Jobs\SummarizeEntry;
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

    public function test_it_does_not_duplicate_entries_on_repeated_fetches(): void
    {
        $feed = Feed::factory()->create();

        $this->fakeFeedIo([file_get_contents(__DIR__.'/../Fixtures/sample-feed.xml')]);
        RefreshFeed::dispatchSync($feed);

        $this->fakeFeedIo([file_get_contents(__DIR__.'/../Fixtures/sample-feed-updated.xml')]);
        RefreshFeed::dispatchSync($feed->refresh());

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
}
