<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_unauthenticated_browser_request_to_the_api_returns_json_instead_of_a_missing_login_redirect(): void
    {
        $this->get('/api/v1/profile')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'برای دسترسی به این بخش وارد حساب شوید.');
    }

    public function test_an_unverified_browser_request_to_the_api_returns_forbidden(): void
    {
        $this->actingAs(User::factory()->unverified()->create(), 'web')
            ->get('/api/v1/profile')
            ->assertForbidden()
            ->assertJsonPath('message', 'ابتدا آدرس ایمیل خود را تأیید کنید.');
    }

    public function test_a_visitor_can_register_and_receive_the_current_user(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Sara Ahmadi',
            'email' => 'sara@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('user.email', 'sara@example.com')
            ->assertJsonPath('user.profile_completed', false);

        $this->assertAuthenticated();
        $this->assertDatabaseHas('user_profiles', ['german_level' => 'none']);
    }
}
