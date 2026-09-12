<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

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
