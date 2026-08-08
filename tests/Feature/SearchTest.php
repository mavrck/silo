<?php

namespace Tests\Feature;

use App\Models\Entry;
use App\Models\Feed;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

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
