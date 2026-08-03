<?php

namespace App\Jobs;

use App\Mail\DigestMail;
use App\Models\Entry;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendDigestEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Cap how many entries are listed in the email body.
     */
    private const DISPLAY_LIMIT = 100;

    public function __construct(
        public readonly User $user,
        public readonly string $frequency,
    ) {}

    public function handle(): void
    {
        $since = $this->frequency === 'weekly' ? now()->subWeek() : now()->subDay();

        $query = Entry::query()
            ->whereHas('feed', fn ($q) => $q->where('user_id', $this->user->id))
            ->unread()
            ->where('published_at', '>=', $since);

        $totalCount = (clone $query)->count();

        if ($totalCount === 0) {
            return;
        }

        $entries = $query->with('feed.category')
            ->orderByDesc('published_at')
            ->limit(self::DISPLAY_LIMIT)
            ->get();

        $grouped = $entries
            ->groupBy(fn (Entry $entry) => $entry->feed->category->name)
            ->map(fn ($entriesInCategory) => $entriesInCategory->groupBy(
                fn (Entry $entry) => $entry->feed->title
            ));

        Mail::to($this->user)->send(new DigestMail(
            user: $this->user,
            groupedEntries: $grouped,
            frequency: $this->frequency,
            totalCount: $totalCount,
            displayedCount: $entries->count(),
        ));
    }
}
