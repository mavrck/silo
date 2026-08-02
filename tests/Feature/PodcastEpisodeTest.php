<?php

namespace Tests\Feature;

use App\Jobs\RefreshFeed;
use App\Models\Entry;
use App\Models\Feed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\FakesFeedIo;
use Tests\TestCase;

class PodcastEpisodeTest extends TestCase
{
    use FakesFeedIo, RefreshDatabase;

    public function test_it_extracts_enclosure_and_itunes_fields_for_an_episode(): void
    {
        $feed = Feed::factory()->create();
        $this->fakeFeedIo([file_get_contents(__DIR__.'/../Fixtures/sample-podcast-feed.xml')]);

        RefreshFeed::dispatchSync($feed);

        $episode = Entry::where('guid', 'https://example.test/episodes/12')->sole();

        $this->assertSame('https://example.test/audio/episode-12.mp3', $episode->enclosure_url);
        $this->assertSame('audio/mpeg', $episode->enclosure_type);
        $this->assertSame(24568563, $episode->enclosure_length);
        $this->assertSame(1535, $episode->duration_seconds); // 25:35
        $this->assertSame(12, $episode->episode_number);
        $this->assertSame(2, $episode->season_number);
        $this->assertSame('https://example.test/artwork/episode-12.jpg', $episode->image_url);
        $this->assertTrue($episode->isPodcastEpisode());
    }

    public function test_it_leaves_podcast_fields_null_for_a_plain_article(): void
    {
        $feed = Feed::factory()->create();
        $this->fakeFeedIo([file_get_contents(__DIR__.'/../Fixtures/sample-podcast-feed.xml')]);

        RefreshFeed::dispatchSync($feed);

        $article = Entry::where('guid', 'https://example.test/articles/1')->sole();

        $this->assertNull($article->enclosure_url);
        $this->assertNull($article->duration_seconds);
        $this->assertNull($article->episode_number);
        $this->assertFalse($article->isPodcastEpisode());
    }

    public function test_it_parses_a_plain_seconds_duration(): void
    {
        $feed = Feed::factory()->create();
        $this->fakeFeedIo([file_get_contents(__DIR__.'/../Fixtures/sample-podcast-feed.xml')]);

        RefreshFeed::dispatchSync($feed);

        $episode = Entry::where('guid', 'https://example.test/episodes/13')->sole();

        $this->assertSame(1535, $episode->duration_seconds);
        $this->assertNull($episode->episode_number);
    }

    public function test_it_parses_an_hh_mm_ss_duration(): void
    {
        $feed = Feed::factory()->create();
        $this->fakeFeedIo([file_get_contents(__DIR__.'/../Fixtures/sample-podcast-feed.xml')]);

        RefreshFeed::dispatchSync($feed);

        $episode = Entry::where('guid', 'https://example.test/episodes/14')->sole();

        $this->assertSame(3723, $episode->duration_seconds); // 1:02:03
        $this->assertSame('video/mp4', $episode->enclosure_type);
    }

    public function test_it_falls_back_to_the_feed_level_artwork_when_the_episode_has_none(): void
    {
        $feed = Feed::factory()->create();
        $this->fakeFeedIo([file_get_contents(__DIR__.'/../Fixtures/sample-podcast-feed.xml')]);

        RefreshFeed::dispatchSync($feed);

        $episode = Entry::where('guid', 'https://example.test/episodes/13')->sole();

        $this->assertSame('https://example.test/artwork/show.jpg', $episode->image_url);
    }
}
