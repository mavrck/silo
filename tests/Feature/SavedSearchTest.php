<?php

namespace Tests\Feature;

use App\Models\SavedSearch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavedSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_save_a_search(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/saved-searches', [
            'name' => 'Unread AI news',
            'q' => 'ai',
            'unread' => 1,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('saved_searches', [
            'user_id' => $user->id,
            'name' => 'Unread AI news',
        ]);

        $saved = SavedSearch::where('name', 'Unread AI news')->sole();
        $this->assertSame('ai', $saved->filters['q']);
        $this->assertSame(1, $saved->filters['unread']);
    }

    public function test_saved_search_names_must_be_unique_per_user(): void
    {
        $user = User::factory()->create();
        SavedSearch::factory()->for($user)->create(['name' => 'Existing']);

        $response = $this->actingAs($user)->post('/saved-searches', [
            'name' => 'Existing',
            'q' => 'anything',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_user_can_delete_their_own_saved_search(): void
    {
        $user = User::factory()->create();
        $search = SavedSearch::factory()->for($user)->create();

        $response = $this->actingAs($user)->delete("/saved-searches/{$search->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('saved_searches', ['id' => $search->id]);
    }

    public function test_user_cannot_delete_another_users_saved_search(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $search = SavedSearch::factory()->for($owner)->create();

        $response = $this->actingAs($intruder)->delete("/saved-searches/{$search->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('saved_searches', ['id' => $search->id]);
    }
}
