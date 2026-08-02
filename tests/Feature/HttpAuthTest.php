<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HttpAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_rejects_requests_without_credentials(): void
    {
        $response = $this->getJson('/api/ping');

        $response->assertUnauthorized();
    }

    public function test_api_accepts_valid_http_basic_credentials(): void
    {
        $user = User::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Basic '.base64_encode("{$user->email}:password"),
        ])->getJson('/api/ping');

        $response->assertOk();
        $response->assertJson(['user' => $user->email]);
    }
}
