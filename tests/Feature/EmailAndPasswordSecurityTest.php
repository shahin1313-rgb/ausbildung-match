<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailAndPasswordSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create(['email_verified_at' => null]);
    }

    public function test_registration_sends_link_and_blocks_sensitive_routes_until_verified(): void
    {
        Notification::fake();
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Sara', 'email' => 'sara@example.com',
            'password' => 'secret123', 'password_confirmation' => 'secret123',
        ])->assertCreated()->assertJsonPath('user.email_verified', false);

        $user = User::where('email', 'sara@example.com')->firstOrFail();
        Notification::assertSentTo($user, VerifyEmail::class);
        $this->getJson('/api/v1/auth/email/status')->assertOk()->assertJsonPath('email_verified', false);
        $this->getJson('/api/v1/profile')->assertForbidden();
        $this->postJson('/api/v1/resumes')->assertForbidden();

        $signed = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id, 'hash' => sha1($user->email),
        ], absolute: false);
        $this->getJson($signed)->assertOk();
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->getJson('/api/v1/profile')->assertOk();
    }

    public function test_email_links_use_the_configured_frontend_origin(): void
    {
        config(['frontend.url' => 'https://frontend.example.com']);
        Notification::fake();
        $user = $this->user();
        $user->sendEmailVerificationNotification();
        Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $notification) use ($user): bool {
            $url = $notification->toMail($user)->actionUrl;
            $this->assertStringStartsWith('https://frontend.example.com/verify-email?', $url);
            parse_str(parse_url($url, PHP_URL_QUERY), $params);
            $this->assertSame((string) $user->id, (string) $params['id']);

            return isset($params['signature'], $params['expires'], $params['hash']);
        });

        Password::sendResetLink(['email' => $user->email]);
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
            $url = $notification->toMail($user)->actionUrl;
            $this->assertStringStartsWith('https://frontend.example.com/reset-password?', $url);
            return str_contains($url, 'token=') && str_contains($url, urlencode($user->email));
        });
    }

    public function test_verification_link_rejects_tampering_expiry_and_changed_email(): void
    {
        $user = $this->user();
        $signed = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id, 'hash' => sha1($user->email),
        ], absolute: false);
        $this->getJson($signed.'&extra=1')->assertForbidden();

        $expired = URL::temporarySignedRoute('verification.verify', now()->subMinute(), [
            'id' => $user->id, 'hash' => sha1($user->email),
        ], absolute: false);
        $this->getJson($expired)->assertForbidden();

        $user->update(['email' => 'new-address@example.com']);
        $this->getJson($signed)->assertForbidden();
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_resend_is_limited_and_verified_user_is_not_resent(): void
    {
        Notification::fake();
        $user = $this->user();
        $this->actingAs($user);
        $this->postJson('/api/v1/auth/email/resend')->assertOk();
        Notification::assertSentToTimes($user, VerifyEmail::class, 1);
        $this->postJson('/api/v1/auth/email/resend')->assertStatus(429);
    }

    public function test_forgot_password_message_is_identical_for_existing_and_unknown_email(): void
    {
        Notification::fake();
        $user = $this->user();
        $existing = $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])->assertOk()->json('message');
        $unknown = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'nobody@example.com'])->assertOk()->json('message');
        $this->assertSame($existing, $unknown);
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_password_recovery_rate_limit_covers_reset_attempts(): void
    {
        Notification::fake();
        $user = $this->user();
        foreach (range(1, 5) as $i) {
            $this->postJson('/api/v1/auth/forgot-password', ['email' => "unknown{$i}@example.com"])->assertOk();
        }
        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'unknown6@example.com'])->assertStatus(429);

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $user->email, 'token' => 'invalid-token',
            'password' => 'newsecret123', 'password_confirmation' => 'newsecret123',
        ])->assertStatus(429); // Same IP limiter also covers reset attempts.
    }

    public function test_expired_token_is_rejected_on_a_fresh_client(): void
    {
        $user = $this->user();
        $token = Password::createToken($user);
        DB::table('password_reset_tokens')->where('email', $user->email)->update(['created_at' => now()->subHours(2)]);
        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $user->email, 'token' => $token,
            'password' => 'newsecret123', 'password_confirmation' => 'newsecret123',
        ])->assertUnprocessable();
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_reset_is_one_use_and_revokes_database_sessions_and_sanctum_tokens(): void
    {
        $user = $this->user();
        $this->insertSession($user);
        $tokenId = $user->createToken('old-device')->accessToken->id;
        $token = Password::createToken($user);
        $payload = [
            'email' => $user->email, 'token' => $token,
            'password' => 'newsecret123', 'password_confirmation' => 'newsecret123',
        ];

        config(['session.driver' => 'database']);
        $this->postJson('/api/v1/auth/reset-password', $payload)->assertOk();
        $this->assertTrue(Hash::check('newsecret123', $user->fresh()->password));
        $this->assertDatabaseMissing('sessions', ['id' => 'old-device-session']);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);
        $this->postJson('/api/v1/auth/reset-password', $payload)->assertUnprocessable();
    }

    public function test_change_password_logs_out_current_user_and_revokes_other_sessions(): void
    {
        $user = $this->user();
        $this->insertSession($user);
        $tokenId = $user->createToken('old-device')->accessToken->id;
        config(['session.driver' => 'database']);
        $this->actingAs($user)->putJson('/api/v1/auth/password', [
            'current_password' => 'password',
            'password' => 'newsecret123', 'password_confirmation' => 'newsecret123',
        ])->assertOk();
        $this->assertGuest();
        $this->assertDatabaseMissing('sessions', ['id' => 'old-device-session']);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);
    }

    public function test_change_email_requires_password_resets_verification_and_sends_to_new_address(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->actingAs($user)->putJson('/api/v1/auth/email', [
            'email' => 'replacement@example.com', 'current_password' => 'incorrect',
        ])->assertUnprocessable();
        $this->assertTrue($user->fresh()->hasVerifiedEmail());

        $this->putJson('/api/v1/auth/email', [
            'email' => 'replacement@example.com', 'current_password' => 'password',
        ])->assertOk();
        $user->refresh();
        $this->assertSame('replacement@example.com', $user->email);
        $this->assertFalse($user->hasVerifiedEmail());
        $this->assertGuest();
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    private function insertSession(User $user): void
    {
        DB::table('sessions')->insert([
            'id' => 'old-device-session', 'user_id' => $user->id, 'ip_address' => '127.0.0.1',
            'user_agent' => 'test-client', 'payload' => '', 'last_activity' => time(),
        ]);
    }
}
