<?php

namespace Tests\Feature;

use App\Jobs\SendDigestEmail;
use App\Mail\DigestMail;
use App\Models\Category;
use App\Models\Entry;
use App\Models\Feed;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendDigestEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_a_digest_grouped_by_category_and_feed(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $techCategory = Category::factory()->for($user)->create(['name' => 'Tech']);
        $feed = Feed::factory()->for($user)->for($techCategory)->create(['title' => 'Hacker News']);
        $entry = Entry::factory()->for($feed)->create([
            'title' => 'PHP 9 released',
            'published_at' => now()->subHours(2),
        ]);

        SendDigestEmail::dispatchSync($user, 'daily');

        Mail::assertSent(DigestMail::class, function (DigestMail $mail) use ($user, $entry) {
            return $mail->hasTo($user->email)
                && $mail->frequency === 'daily'
                && $mail->totalCount === 1
                && $mail->displayedCount === 1
                && $mail->groupedEntries->get('Tech')?->get('Hacker News')?->first()?->is($entry);
        });
    }

    public function test_it_does_not_send_when_there_are_no_unread_entries_in_the_window(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $feed = Feed::factory()->for($user)->create();
        Entry::factory()->read()->for($feed)->create(['published_at' => now()]);
        Entry::factory()->for($feed)->create(['published_at' => now()->subWeek()]);

        SendDigestEmail::dispatchSync($user, 'daily');

        Mail::assertNothingSent();
    }

    public function test_weekly_digest_excludes_entries_older_than_a_week(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $feed = Feed::factory()->for($user)->create();
        $recent = Entry::factory()->for($feed)->create(['published_at' => now()->subDays(3)]);
        Entry::factory()->for($feed)->create(['published_at' => now()->subDays(10)]);

        SendDigestEmail::dispatchSync($user, 'weekly');

        Mail::assertSent(DigestMail::class, fn (DigestMail $mail) => $mail->totalCount === 1);
    }

    public function test_it_excludes_another_users_entries(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        Feed::factory()->create()->entries()->save(
            Entry::factory()->make(['published_at' => now()])
        );

        SendDigestEmail::dispatchSync($user, 'daily');

        Mail::assertNothingSent();
    }
}
