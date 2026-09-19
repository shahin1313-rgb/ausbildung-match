<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Company;
use App\Models\Opportunity;
use App\Models\Resume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResumeSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_executable_renamed_as_pdf_is_rejected(): void
    {
        Storage::fake('resumes');
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/v1/resumes', [
            'resume' => UploadedFile::fake()->createWithContent('resume.pdf', '<?php echo "unsafe";'),
            'consent_resume_processing' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors('resume');

        $this->assertDatabaseCount('resumes', 0);
        $this->assertSame([], Storage::disk('resumes')->allFiles());
    }

    public function test_uploaded_resume_uses_private_disk_and_random_name(): void
    {
        Storage::fake('resumes');
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/v1/resumes', [
            'resume' => UploadedFile::fake()->createWithContent('My CV.pdf', "%PDF-1.4\nsecure resume"),
            'consent_resume_processing' => true,
        ])->assertCreated();

        $resume = Resume::query()->sole();
        $this->assertSame('resumes', $resume->disk);
        $this->assertSame('My CV.pdf', $resume->original_name);
        $this->assertMatchesRegularExpression('/^'.$user->id.'\/[0-9a-f-]{36}\.pdf$/', $resume->path);
        Storage::disk('resumes')->assertExists($resume->path);
    }

    public function test_upload_fails_closed_when_malware_scanner_is_unavailable(): void
    {
        Storage::fake('resumes');
        config([
            'resume_security.malware_scan' => true,
            'resume_security.scanner_binary' => 'missing-resume-scanner-binary',
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/v1/resumes', [
            'resume' => UploadedFile::fake()->createWithContent('resume.pdf', "%PDF-1.4\nsecure resume"),
            'consent_resume_processing' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors('resume');

        $this->assertDatabaseCount('resumes', 0);
    }

    public function test_employer_can_only_download_resume_bound_when_application_was_sent(): void
    {
        Storage::fake('resumes');
        $employer = User::factory()->create();
        $company = Company::factory()->for($employer, 'owner')->create();
        $opportunity = Opportunity::factory()->create(['company_id' => $company->id]);
        $candidate = User::factory()->create();

        $old = $this->resume($candidate, 'old.pdf', 'old.pdf', true);
        $application = Application::query()->create([
            'user_id' => $candidate->id,
            'opportunity_id' => $opportunity->id,
            'resume_id' => $old->id,
            'status' => 'applied',
            'applied_at' => now(),
            'data_sharing_consent_at' => now(),
        ]);
        $old->update(['is_primary' => false]);
        $this->resume($candidate, 'new.pdf', 'new.pdf', true);

        $response = $this->actingAs($employer)
            ->get("/api/v1/employer/applications/{$application->id}/resume")
            ->assertOk()
            ->assertDownload('old.pdf');

        $cacheControl = (string) $response->headers->get('Cache-Control');
        $this->assertStringContainsString('private', $cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('max-age=0', $cacheControl);
    }

    private function resume(User $user, string $name, string $path, bool $primary): Resume
    {
        Storage::disk('resumes')->put($path, "%PDF-1.4\nresume");

        return Resume::query()->create([
            'user_id' => $user->id,
            'original_name' => $name,
            'disk' => 'resumes',
            'path' => $path,
            'mime_type' => 'application/pdf',
            'size_bytes' => 16,
            'status' => 'uploaded',
            'is_primary' => $primary,
            'processing_consent_at' => now(),
            'retention_until' => now()->addDay(),
        ]);
    }
}
