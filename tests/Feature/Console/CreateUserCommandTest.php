<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateUserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_user(): void
    {
        $this->artisan('user:create', [
            'name' => 'Bill Condo',
            'email' => 'bill@example.com',
            'password' => 'correct-horse-battery',
        ])
            ->assertSuccessful();

        $user = User::where('email', 'bill@example.com')->sole();
        $this->assertSame('Bill Condo', $user->name);
        $this->assertTrue(Hash::check('correct-horse-battery', $user->password));
    }

    public function test_it_works_regardless_of_the_registrations_enabled_flag(): void
    {
        config(['app.registrations_enabled' => false]);

        $this->artisan('user:create', [
            'name' => 'Bill Condo',
            'email' => 'bill@example.com',
            'password' => 'correct-horse-battery',
        ])
            ->assertSuccessful();

        $this->assertDatabaseHas('users', ['email' => 'bill@example.com']);
    }

    public function test_it_rejects_a_duplicate_email(): void
    {
        User::factory()->create(['email' => 'bill@example.com']);

        $this->artisan('user:create', [
            'name' => 'Bill Condo',
            'email' => 'bill@example.com',
            'password' => 'correct-horse-battery',
        ])
            ->assertFailed();

        $this->assertSame(1, User::where('email', 'bill@example.com')->count());
    }

    public function test_it_rejects_an_invalid_email(): void
    {
        $this->artisan('user:create', [
            'name' => 'Bill Condo',
            'email' => 'not-an-email',
            'password' => 'correct-horse-battery',
        ])
            ->assertFailed();

        $this->assertDatabaseMissing('users', ['name' => 'Bill Condo']);
    }

    public function test_it_rejects_a_weak_password(): void
    {
        $this->artisan('user:create', [
            'name' => 'Bill Condo',
            'email' => 'bill@example.com',
            'password' => '123',
        ])
            ->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'bill@example.com']);
    }
}
