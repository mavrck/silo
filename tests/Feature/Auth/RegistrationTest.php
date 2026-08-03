<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_registration_screen_redirects_to_login_when_disabled(): void
    {
        config(['app.registrations_enabled' => false]);

        $response = $this->get('/register');

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status', 'Registration is currently closed.');
    }

    public function test_registration_is_rejected_when_disabled(): void
    {
        config(['app.registrations_enabled' => false]);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    public function test_welcome_page_hides_sign_up_when_registration_is_disabled(): void
    {
        config(['app.registrations_enabled' => false]);

        $response = $this->get('/');

        $response->assertInertia(fn ($page) => $page->where('canRegister', false));
    }

    public function test_welcome_page_shows_sign_up_when_registration_is_enabled(): void
    {
        config(['app.registrations_enabled' => true]);

        $response = $this->get('/');

        $response->assertInertia(fn ($page) => $page->where('canRegister', true));
    }
}
