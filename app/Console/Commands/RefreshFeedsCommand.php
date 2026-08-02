<?php

namespace App\Console\Commands;

use App\Jobs\RefreshFeed;
use App\Models\Feed;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('feeds:refresh')]
#[Description('Dispatch RefreshFeed jobs for every feed due for a refresh')]
class RefreshFeedsCommand extends Command
{
    public function handle(): void
    {
        $dueFeeds = Feed::query()
            ->where(function ($query) {
                $query->whereNull('last_fetched_at')
                    ->orWhereRaw(
                        'last_fetched_at <= ? - INTERVAL fetch_interval_minutes MINUTE',
                        [now()]
                    );
            })
            ->get();

        foreach ($dueFeeds as $feed) {
            RefreshFeed::dispatch($feed);
        }

        $this->info("Dispatched {$dueFeeds->count()} feed refresh job(s).");
    }
}
