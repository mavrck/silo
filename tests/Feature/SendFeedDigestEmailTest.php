<?php

namespace Tests\Feature;

use App\Jobs\SendFeedDigestEmail;
use App\Mail\FeedDigestMail;
use App\Models\Entry;
use App\Models\Feed;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendFeedDigestEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_a_digest_of_the_feeds_unread_entries(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $feed = Feed::factory()->for($user)->create(['title' => 'Hacker News']);
        $entry = Entry::factory()->for($feed)->create([
            'title' => 'PHP 9 released',
            'published_at' => now()->subHours(2),
        ]);

        SendFeedDigestEmail::dispatchSync($feed, 'daily');

        Mail::assertSent(FeedDigestMail::class, function (FeedDigestMail $mail) use ($user, $feed, $entry) {
            return $mail->hasTo($user->email)
                && $mail->feed->is($feed)
                && $mail->frequency === 'daily'
                && $mail->totalCount === 1
                && $mail->entries->first()->is($entry);
        });
    }

    public function test_it_does_not_send_when_there_are_no_unread_entries_in_the_window(): void
    {
        Mail::fake();

        $feed = Feed::factory()->create();
        Entry::factory()->read()->for($feed)->create(['published_at' => now()]);
        Entry::factory()->for($feed)->create(['published_at' => now()->subWeek()]);

        SendFeedDigestEmail::dispatchSync($feed, 'daily');

        Mail::assertNothingSent();
    }

    public function test_it_only_includes_this_feeds_entries(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $feed = Feed::factory()->for($user)->create();
        $otherFeed = Feed::factory()->for($user)->create();
        Entry::factory()->for($otherFeed)->create(['published_at' => now()]);

        SendFeedDigestEmail::dispatchSync($feed, 'daily');

        Mail::assertNothingSent();
    }

    public function test_it_includes_entries_since_the_last_send_even_outside_the_default_window(): void
    {
        Mail::fake();

        $feed = Feed::factory()->create(['digest_last_sent_at' => now()->subHours(36)]);
        Entry::factory()->for($feed)->create(['published_at' => now()->subHours(30)]);

        SendFeedDigestEmail::dispatchSync($feed, 'daily');

        Mail::assertSent(FeedDigestMail::class, fn (FeedDigestMail $mail) => $mail->totalCount === 1);
    }

    public function test_the_lookback_is_capped_at_twice_the_cadence(): void
    {
        Mail::fake();

        $feed = Feed::factory()->create(['digest_last_sent_at' => now()->subDays(10)]);
        Entry::factory()->for($feed)->create(['published_at' => now()->subDays(5)]);
        Entry::factory()->for($feed)->create(['published_at' => now()->subHours(6)]);

        SendFeedDigestEmail::dispatchSync($feed, 'daily');

        Mail::assertSent(FeedDigestMail::class, fn (FeedDigestMail $mail) => $mail->totalCount === 1);
    }

    public function test_a_successful_send_advances_digest_last_sent_at(): void
    {
        Mail::fake();

        $feed = Feed::factory()->create(['digest_last_sent_at' => null]);
        Entry::factory()->for($feed)->create(['published_at' => now()]);

        SendFeedDigestEmail::dispatchSync($feed, 'daily');

        $this->assertNotNull($feed->fresh()->digest_last_sent_at);
        $this->assertTrue($feed->fresh()->digest_last_sent_at->greaterThan(now()->subMinute()));
    }

    public function test_a_run_with_nothing_unread_still_advances_digest_last_sent_at(): void
    {
        Mail::fake();

        $feed = Feed::factory()->create(['digest_last_sent_at' => null]);
        Entry::factory()->read()->for($feed)->create(['published_at' => now()]);

        SendFeedDigestEmail::dispatchSync($feed, 'daily');

        Mail::assertNothingSent();
        $this->assertNotNull($feed->fresh()->digest_last_sent_at);
    }
}
