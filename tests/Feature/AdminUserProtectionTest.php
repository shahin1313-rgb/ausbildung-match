<?php

namespace Tests\Feature;

use App\Models\AdminAuditLog;
use App\Models\User;
use App\Services\AdminUserUpdater;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdminUserProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_admin_cannot_promote_another_user(): void
    {
        $actor = User::factory()->create([
            'password' => Hash::make('secret-pass'),
            'is_admin' => true,
            'is_super_admin' => false,
        ]);
        $target = User::factory()->create(['is_admin' => false]);

        $this->expectException(AuthorizationException::class);

        app(AdminUserUpdater::class)->update($actor, $target, ['is_admin' => true], 'secret-pass');
    }

    public function test_super_admin_cannot_change_own_admin_role(): void
    {
        $actor = User::factory()->create([
            'password' => Hash::make('secret-pass'),
            'is_admin' => true,
            'is_super_admin' => true,
        ]);

        $this->expectException(ValidationException::class);

        app(AdminUserUpdater::class)->update($actor, $actor, ['is_admin' => false], 'secret-pass');
    }

    public function test_last_admin_cannot_be_demoted(): void
    {
        $actor = User::factory()->create([
            'password' => Hash::make('secret-pass'),
            'is_admin' => true,
            'is_super_admin' => true,
        ]);
        $target = User::factory()->create(['is_admin' => true]);
        $actor->forceFill(['is_admin' => false])->saveQuietly();

        $this->expectException(ValidationException::class);

        app(AdminUserUpdater::class)->update($actor, $target, ['is_admin' => false], 'secret-pass');
    }

    public function test_wrong_password_rejects_sensitive_change(): void
    {
        $actor = User::factory()->create([
            'password' => Hash::make('secret-pass'),
            'is_admin' => true,
            'is_super_admin' => true,
        ]);
        $target = User::factory()->create();

        $this->expectException(ValidationException::class);

        app(AdminUserUpdater::class)->update($actor, $target, ['is_admin' => true], 'wrong-pass');
    }

    public function test_role_change_is_audited(): void
    {
        $actor = User::factory()->create([
            'password' => Hash::make('secret-pass'),
            'is_admin' => true,
            'is_super_admin' => true,
        ]);
        $target = User::factory()->create(['is_admin' => false]);

        app(AdminUserUpdater::class)->update($actor, $target, ['is_admin' => true], 'secret-pass');

        $this->assertTrue($target->fresh()->is_admin);
        $log = AdminAuditLog::query()->sole();
        $this->assertSame($actor->id, $log->actor_id);
        $this->assertSame($target->id, $log->subject_id);
        $this->assertSame('admin_role_changed', $log->event);
        $this->assertFalse($log->old_values['is_admin']);
        $this->assertTrue($log->new_values['is_admin']);
    }

    public function test_email_change_requires_super_admin_and_password_and_resets_verification(): void
    {
        $actor = User::factory()->create([
            'password' => Hash::make('secret-pass'),
            'is_admin' => true,
            'is_super_admin' => true,
        ]);
        $target = User::factory()->create([
            'email' => 'old@example.test',
            'email_verified_at' => now(),
        ]);

        app(AdminUserUpdater::class)->update(
            $actor,
            $target,
            ['email' => 'new@example.test'],
            'secret-pass',
        );

        $target->refresh();
        $this->assertSame('new@example.test', $target->email);
        $this->assertNull($target->email_verified_at);
        $this->assertDatabaseHas('admin_audit_logs', [
            'actor_id' => $actor->id,
            'subject_id' => $target->id,
            'event' => 'admin_user_updated',
        ]);
    }
}
