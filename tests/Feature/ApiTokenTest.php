<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class ApiTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_an_api_token(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post('/profile/api-tokens', ['name' => 'My laptop']);

        $response->assertRedirect();
        $response->assertSessionHas('token');

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
            'name' => 'My laptop',
        ]);
    }

    public function test_token_name_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post('/profile/api-tokens', ['name' => '']);

        $response->assertSessionHasErrors('name');
    }

    public function test_user_can_regenerate_their_own_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('My laptop');

        $response = $this
            ->actingAs($user)
            ->post("/profile/api-tokens/{$token->accessToken->id}/regenerate");

        $response->assertRedirect();
        $response->assertSessionHas('token');

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'My laptop',
        ]);
    }

    public function test_user_cannot_regenerate_another_users_token(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $token = $owner->createToken('My laptop');

        $response = $this
            ->actingAs($attacker)
            ->post("/profile/api-tokens/{$token->accessToken->id}/regenerate");

        $response->assertNotFound();

        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);
    }

    public function test_user_can_revoke_their_own_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('My laptop');

        $response = $this
            ->actingAs($user)
            ->delete("/profile/api-tokens/{$token->accessToken->id}");

        $response->assertRedirect();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);
    }

    public function test_user_cannot_revoke_another_users_token(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $token = $owner->createToken('My laptop');

        $response = $this
            ->actingAs($attacker)
            ->delete("/profile/api-tokens/{$token->accessToken->id}");

        $response->assertNotFound();

        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);
    }

    public function test_authenticated_route_rejects_requests_without_a_token(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertUnauthorized();
    }

    public function test_authenticated_route_accepts_a_valid_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('My laptop');

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson('/api/user');

        $response->assertOk();
        $response->assertJson([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }

    public function test_authenticated_route_rejects_a_revoked_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('My laptop');
        PersonalAccessToken::findToken($token->plainTextToken)->delete();

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson('/api/user');

        $response->assertUnauthorized();
    }
}
