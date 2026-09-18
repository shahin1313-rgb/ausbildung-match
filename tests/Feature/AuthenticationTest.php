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
            'accept_terms' => true,
            'accept_privacy' => true,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('user.email', 'sara@example.com')
            ->assertJsonPath('user.profile_completed', false);

        $this->assertAuthenticated();
        $this->assertDatabaseHas('user_profiles', ['german_level' => 'none']);
    }

    public function test_registration_validation_messages_are_clear_and_persian(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => '',
            'email' => 'not-an-email',
            'password' => '12345678',
            'password_confirmation' => 'different',
            'accept_terms' => false,
            'accept_privacy' => false,
        ])->assertUnprocessable()
            ->assertJsonPath('errors.name.0', 'نام و نام خانوادگی را وارد کنید.')
            ->assertJsonPath('errors.email.0', 'آدرس ایمیل معتبر نیست؛ نمونه صحیح: name@example.com')
            ->assertJsonPath('errors.password.0', 'رمز عبور و تکرار آن یکسان نیستند.')
            ->assertJsonPath('errors.accept_terms.0', 'برای ساخت حساب باید شرایط استفاده را بپذیرید.')
            ->assertJsonPath('errors.accept_privacy.0', 'برای ساخت حساب باید سیاست حریم خصوصی را تأیید کنید.');
    }

    public function test_login_validation_messages_are_clear_and_persian(): void
    {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'invalid',
            'password' => '',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'آدرس ایمیل معتبر نیست؛ نمونه صحیح: name@example.com')
            ->assertJsonPath('errors.password.0', 'رمز عبور را وارد کنید.');
    }
}
