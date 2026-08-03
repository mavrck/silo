<?php

namespace Tests\Feature\Console;

use App\Jobs\SendDigestEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class SendDigestsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_dispatches_a_job_for_each_user_subscribed_at_the_given_frequency(): void
    {
        Bus::fake();

        $dailyUser = User::factory()->create(['digest_frequency' => 'daily']);
        User::factory()->create(['digest_frequency' => 'weekly']);
        User::factory()->create(['digest_frequency' => 'off']);

        $this->artisan('digests:send', ['frequency' => 'daily'])->assertSuccessful();

        Bus::assertDispatched(SendDigestEmail::class, fn (SendDigestEmail $job) => $job->user->is($dailyUser) && $job->frequency === 'daily');
        Bus::assertDispatchedTimes(SendDigestEmail::class, 1);
    }

    public function test_it_rejects_an_invalid_frequency(): void
    {
        $this->artisan('digests:send', ['frequency' => 'hourly'])->assertFailed();
    }
}
