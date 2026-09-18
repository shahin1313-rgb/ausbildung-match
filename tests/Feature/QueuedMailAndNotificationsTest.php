<?php

namespace Tests\Feature;

use App\Mail\WeeklyOpportunityDigestMail;
use App\Models\User;
use App\Notifications\QueuedResetPassword;
use App\Notifications\QueuedVerifyEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class QueuedMailAndNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_auth_notifications_are_queueable(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email_verified_at' => null]);

        $user->sendEmailVerificationNotification();
        Password::sendResetLink(['email' => $user->email]);

        Notification::assertSentTo($user, QueuedVerifyEmail::class, fn ($notification): bool => $notification instanceof ShouldQueue);
        Notification::assertSentTo($user, QueuedResetPassword::class, fn ($notification): bool => $notification instanceof ShouldQueue);
    }

    public function test_weekly_digest_mail_is_queueable(): void
    {
        $mail = new WeeklyOpportunityDigestMail(collect(), 'https://ausbildung.example');

        $this->assertInstanceOf(ShouldQueue::class, $mail);
        $this->assertSame(3, $mail->tries);
        $this->assertSame([60, 300, 900], $mail->backoff);
    }
}
