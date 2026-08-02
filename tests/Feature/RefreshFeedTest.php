<?php

namespace Tests\Feature;

use App\Jobs\RefreshFeed;
use App\Models\Feed;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
