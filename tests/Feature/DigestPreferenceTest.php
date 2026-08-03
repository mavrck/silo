<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DigestPreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_set_their_digest_frequency(): void
    {
        $user = User::factory()->create(['digest_frequency' => 'off']);

        $response = $this
            ->actingAs($user)
            ->patch('/profile/digest', ['digest_frequency' => 'weekly']);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertSame('weekly', $user->fresh()->digest_frequency);
    }

    public function test_digest_frequency_must_be_a_valid_option(): void
    {
        $user = User::factory()->create(['digest_frequency' => 'off']);

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->patch('/profile/digest', ['digest_frequency' => 'hourly']);

        $response->assertSessionHasErrors('digest_frequency');
        $this->assertSame('off', $user->fresh()->digest_frequency);
    }

    public function test_it_requires_authentication(): void
    {
        $response = $this->patch('/profile/digest', ['digest_frequency' => 'daily']);

        $response->assertRedirect('/login');
    }
}
