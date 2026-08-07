<?php

namespace App\Jobs;

use App\Mail\FeedDigestMail;
use App\Models\Feed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendFeedDigestEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Cap how many entries are listed in the email body.
     */
    private const DISPLAY_LIMIT = 100;

    public function __construct(
        public readonly Feed $feed,
        public readonly string $frequency,
    ) {}

    public function handle(): void
    {
        $defaultSince = $this->frequency === 'weekly' ? now()->subWeek() : now()->subDay();
        $cap = $this->frequency === 'weekly' ? now()->subWeeks(2) : now()->subDays(2);
        $since = $this->feed->digest_last_sent_at?->max($cap) ?? $defaultSince;

        $query = $this->feed->entries()
            ->unread()
            ->where('published_at', '>=', $since);

        $totalCount = (clone $query)->count();

        if ($totalCount === 0) {
            $this->feed->recordDigestSent();

            return;
        }

        $entries = $query->orderByDesc('published_at')
            ->limit(self::DISPLAY_LIMIT)
            ->get();

        Mail::to($this->feed->user)->send(new FeedDigestMail(
            user: $this->feed->user,
            feed: $this->feed,
            entries: $entries,
            frequency: $this->frequency,
            totalCount: $totalCount,
            displayedCount: $entries->count(),
        ));

        $this->feed->recordDigestSent();
    }
}
