<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminMultiFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_users_support_totp_and_recovery_codes(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertInstanceOf(HasAppAuthentication::class, $admin);
        $this->assertInstanceOf(HasAppAuthenticationRecovery::class, $admin);
        $this->assertTrue(Schema::hasColumns('users', [
            'app_authentication_secret',
            'app_authentication_recovery_codes',
        ]));
    }

    public function test_admin_panel_requires_app_mfa_with_recovery_codes(): void
    {
        $panel = filament()->getPanel('admin');
        $provider = $panel->getMultiFactorAuthenticationProviders()['app'] ?? null;

        $this->assertTrue($panel->isMultiFactorAuthenticationRequired());
        $this->assertInstanceOf(AppAuthentication::class, $provider);
        $this->assertTrue($provider->isRecoverable());
    }

    public function test_mfa_credentials_are_encrypted_and_never_serialized(): void
    {
        $admin = User::factory()->admin()->create();

        $admin->saveAppAuthenticationSecret('totp-secret');
        $admin->saveAppAuthenticationRecoveryCodes(['recovery-code']);

        $this->assertArrayNotHasKey('app_authentication_secret', $admin->toArray());
        $this->assertArrayNotHasKey('app_authentication_recovery_codes', $admin->toArray());
        $this->assertNotSame('totp-secret', $admin->getRawOriginal('app_authentication_secret'));
        $this->assertStringNotContainsString('recovery-code', $admin->getRawOriginal('app_authentication_recovery_codes'));
    }
}
