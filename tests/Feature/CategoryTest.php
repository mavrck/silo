<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_index_is_displayed(): void
    {
        $user = User::factory()->create();
        Category::factory()->for($user)->create(['name' => 'Tech']);

        $response = $this->actingAs($user)->get('/categories');

        $response->assertOk();
    }

    public function test_user_can_create_a_category(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/categories', [
            'name' => 'News',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('categories', [
            'user_id' => $user->id,
            'name' => 'News',
        ]);
    }

    public function test_category_name_must_be_unique_per_user(): void
    {
        $user = User::factory()->create();
        Category::factory()->for($user)->create(['name' => 'News']);

        $response = $this->actingAs($user)->post('/categories', [
            'name' => 'News',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_different_users_can_have_categories_with_the_same_name(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        Category::factory()->for($userA)->create(['name' => 'News']);

        $response = $this->actingAs($userB)->post('/categories', [
            'name' => 'News',
        ]);

        $response->assertSessionDoesntHaveErrors();
    }

    public function test_user_can_rename_their_own_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create(['name' => 'Old']);

        $response = $this->actingAs($user)->patch("/categories/{$category->id}", [
            'name' => 'New',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'New']);
    }

    public function test_user_cannot_rename_another_users_category(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $category = Category::factory()->for($owner)->create(['name' => 'Old']);

        $response = $this->actingAs($intruder)->patch("/categories/{$category->id}", [
            'name' => 'New',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Old']);
    }

    public function test_user_can_delete_their_own_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();

        $response = $this->actingAs($user)->delete("/categories/{$category->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_user_cannot_delete_another_users_category(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $category = Category::factory()->for($owner)->create();

        $response = $this->actingAs($intruder)->delete("/categories/{$category->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_guests_cannot_access_categories(): void
    {
        $response = $this->get('/categories');

        $response->assertRedirect('/login');
    }
}
