<?php

namespace App\Console\Commands;

use App\Jobs\SendDigestEmail;
use App\Jobs\SendFeedDigestEmail;
use App\Models\Feed;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('digests:send {frequency : "daily" or "weekly"}')]
#[Description('Dispatch digest emails to users subscribed at the given frequency')]
class SendDigestsCommand extends Command
{
    public function handle(): int
    {
        $frequency = $this->argument('frequency');

        if (! in_array($frequency, ['daily', 'weekly'], true)) {
            $this->error('Frequency must be "daily" or "weekly".');

            return self::FAILURE;
        }

        $users = User::query()->where('digest_frequency', $frequency)->get();

        foreach ($users as $user) {
            SendDigestEmail::dispatch($user, $frequency);
        }

        $feeds = Feed::query()->where('digest_frequency', $frequency)->get();

        foreach ($feeds as $feed) {
            SendFeedDigestEmail::dispatch($feed, $frequency);
        }

        $this->info("Dispatched {$users->count()} {$frequency} user digest job(s) and {$feeds->count()} {$frequency} feed digest job(s).");

        return self::SUCCESS;
    }
}
