<?php

namespace Tests\Feature;

use App\Models\Entry;
use App\Models\Feed;
use App\Models\User;
use Tests\TestCase;

/**
 * Deliberately does not use RefreshDatabase: InnoDB only flushes a FULLTEXT
 * index's in-memory cache on commit, so rows inserted inside RefreshDatabase's
 * wrapping (and never-committed) transaction are invisible to MATCH AGAINST
 * queries even within the same connection. These tests commit for real and
 * clean up manually instead.
 */
class SearchTest extends TestCase
{
    protected function tearDown(): void
    {
        User::query()->delete();

        parent::tearDown();
    }

    public function test_index_can_search_entry_content(): void
    {
        $user = User::factory()->create();
        $feed = Feed::factory()->for($user)->create();
        $match = Entry::factory()->for($feed)->create([
            'title' => 'A guide to distributed systems',
            'content' => 'Consensus algorithms like Raft and Paxos.',
        ]);
        Entry::factory()->for($feed)->create([
            'title' => 'A recipe for sourdough bread',
            'content' => 'Flour, water, salt, and time.',
        ]);

        $response = $this->actingAs($user)->get('/entries?q=distributed');

        $response->assertInertia(fn ($page) => $page
            ->has('entries.data', 1)
            ->where('entries.data.0.id', $match->id)
        );
    }

    public function test_search_only_matches_the_users_own_entries(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $feed = Feed::factory()->for($stranger)->create();
        Entry::factory()->for($feed)->create([
            'title' => 'A guide to distributed systems',
            'content' => 'Consensus algorithms like Raft and Paxos.',
        ]);

        $response = $this->actingAs($user)->get('/entries?q=distributed');

        $response->assertInertia(fn ($page) => $page->has('entries.data', 0));
    }
}
