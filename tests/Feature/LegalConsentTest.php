<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Company;
use App\Models\Opportunity;
use App\Models\Resume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LegalConsentTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_requires_and_records_separate_legal_acceptances(): void
    {
        Notification::fake();
        $payload = [
            'name' => 'Sara Ahmadi',
            'email' => 'sara@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ];

        $this->postJson('/api/v1/auth/register', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['accept_terms', 'accept_privacy']);

        $this->postJson('/api/v1/auth/register', [
            ...$payload,
            'accept_terms' => true,
            'accept_privacy' => true,
        ])->assertCreated();

        $user = User::query()->where('email', 'sara@example.com')->firstOrFail();
        $this->assertNotNull($user->terms_accepted_at);
        $this->assertNotNull($user->privacy_accepted_at);
        $this->assertSame(config('legal.terms_version'), $user->terms_version);
        $this->assertSame(config('legal.privacy_version'), $user->privacy_version);
    }

    public function test_resume_upload_requires_consent_and_gets_a_retention_deadline(): void
    {
        Storage::fake('resumes');
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/v1/resumes', [
            'resume' => UploadedFile::fake()->createWithContent('lebenslauf.pdf', "%PDF-1.4\nsecure resume"),
        ])->assertUnprocessable()->assertJsonValidationErrors('consent_resume_processing');

        $this->actingAs($user)->postJson('/api/v1/resumes', [
            'resume' => UploadedFile::fake()->createWithContent('lebenslauf.pdf', "%PDF-1.4\nsecure resume"),
            'consent_resume_processing' => true,
        ])->assertCreated();

        $resume = Resume::query()->firstOrFail();
        $this->assertNotNull($resume->processing_consent_at);
        $this->assertTrue($resume->retention_until->between(
            now()->addDays(config('legal.resume_retention_days'))->subMinute(),
            now()->addDays(config('legal.resume_retention_days'))->addMinute(),
        ));
    }

    public function test_direct_application_requires_and_records_data_sharing_consent(): void
    {
        $company = Company::factory()->for(User::factory(), 'owner')->create();
        $opportunity = Opportunity::factory()->create([
            'company_id' => $company->id,
            'application_url' => null,
        ]);
        $candidate = User::factory()->create();

        $this->actingAs($candidate)
            ->postJson("/api/v1/applications/{$opportunity->slug}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('consent_data_sharing');

        $this->actingAs($candidate)
            ->postJson("/api/v1/applications/{$opportunity->slug}", ['consent_data_sharing' => true])
            ->assertCreated();

        $this->assertNotNull(Application::query()->firstOrFail()->data_sharing_consent_at);
    }

    public function test_expired_resume_files_and_records_are_purged(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        Storage::disk('local')->put('resumes/expired.pdf', 'expired');
        Storage::disk('local')->put('resumes/current.pdf', 'current');

        $base = [
            'user_id' => $user->id,
            'original_name' => 'lebenslauf.pdf',
            'disk' => 'local',
            'mime_type' => 'application/pdf',
            'size_bytes' => 7,
            'status' => 'uploaded',
            'is_primary' => false,
            'processing_consent_at' => now(),
        ];
        $expired = Resume::query()->create([
            ...$base, 'path' => 'resumes/expired.pdf', 'retention_until' => now()->subMinute(),
        ]);
        $current = Resume::query()->create([
            ...$base, 'path' => 'resumes/current.pdf', 'retention_until' => now()->addDay(),
        ]);

        $this->artisan('resumes:purge-expired')->assertSuccessful();

        $this->assertModelMissing($expired);
        $this->assertModelExists($current);
        Storage::disk('local')->assertMissing('resumes/expired.pdf');
        Storage::disk('local')->assertExists('resumes/current.pdf');
    }

    public function test_meta_exposes_public_legal_configuration(): void
    {
        config(['legal.provider.name' => 'Example GmbH']);

        $this->getJson('/api/v1/meta')
            ->assertOk()
            ->assertJsonPath('legal.provider.name', 'Example GmbH')
            ->assertJsonPath('legal.resume_retention_days', 180);
    }
}
