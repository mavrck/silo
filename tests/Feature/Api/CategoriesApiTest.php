<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spectator\Spectator;
use Tests\TestCase;

class CategoriesApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Spectator::using('openapi.yaml');
    }

    public function test_it_requires_authentication(): void
    {
        $response = $this->getJson('/api/categories');

        $response->assertValidRequest();
        $response->assertValidResponse(401);
    }

    public function test_it_lists_only_the_authenticated_users_categories(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        Category::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/categories');

        $response->assertValidRequest();
        $response->assertValidResponse(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.type', 'categories');
        $response->assertJsonPath('data.0.id', (string) $category->id);
    }

    public function test_it_shows_a_single_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/categories/{$category->id}");

        $response->assertValidRequest();
        $response->assertValidResponse(200);
        $response->assertJsonPath('data.attributes.name', $category->name);
    }

    public function test_it_forbids_viewing_another_users_category(): void
    {
        $user = User::factory()->create();
        $otherUsersCategory = Category::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/categories/{$otherUsersCategory->id}");

        $response->assertValidRequest();
        $response->assertValidResponse(403);
    }

    public function test_it_returns_404_for_a_missing_category(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/categories/999999');

        $response->assertValidRequest();
        $response->assertValidResponse(404);
    }
}
