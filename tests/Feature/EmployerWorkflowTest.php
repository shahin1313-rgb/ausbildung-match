<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Category;
use App\Models\Company;
use App\Models\Opportunity;
use App\Models\Resume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployerWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_company_requires_admin_verification_before_publishing_opportunities(): void
    {
        $employer = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $category = Category::factory()->create();

        $this->actingAs($employer)->postJson('/api/v1/employer/company', [
            'name' => 'Berlin Technik GmbH',
            'contact_email' => 'jobs@berlin-technik.de',
            'city' => 'Berlin',
            'website' => 'https://berlin-technik.de',
        ])->assertCreated()
            ->assertJsonPath('company.name', 'Berlin Technik GmbH')
            ->assertJsonPath('company.status', 'pending');

        $payload = [
            'category_id' => $category->id,
            'title_fa' => 'کارآموز مکاترونیک',
            'title_de' => 'Ausbildung Mechatroniker/in',
            'description_fa' => 'آموزش عملی در کنار تیم فنی شرکت.',
            'city' => 'Berlin',
            'training_type' => 'dual',
            'required_german_level' => 'b1',
            'accepts_international' => true,
            'visa_support' => 'possible',
            'skills' => ['Technik', 'Teamarbeit'],
            'status' => 'published',
        ];

        $this->actingAs($employer)
            ->postJson('/api/v1/employer/opportunities', $payload)
            ->assertForbidden();

        $company = $employer->company()->firstOrFail();
        $this->actingAs($admin);
        $company->update([
            'status' => 'verified',
            'verification_method' => 'admin_review',
        ]);
        $employer->unsetRelation('company');

        $response = $this->actingAs($employer)->postJson('/api/v1/employer/opportunities', $payload);

        $response->assertCreated()
            ->assertJsonPath('opportunity.status', 'published')
            ->assertJsonPath('opportunity.applicants_count', 0);

        $this->assertDatabaseHas('opportunities', [
            'company_id' => $employer->company->id,
            'employer_name' => 'Berlin Technik GmbH',
            'application_url' => null,
            'status' => 'published',
        ]);
        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'status' => 'verified',
            'verification_method' => 'admin_review',
            'verified_by' => $admin->id,
        ]);
    }

    public function test_pending_company_cannot_list_or_manage_opportunities(): void
    {
        $employer = User::factory()->create();
        Company::factory()->for($employer, 'owner')->create([
            'status' => 'pending',
            'verification_method' => null,
            'verified_at' => null,
            'verified_by' => null,
        ]);

        $this->actingAs($employer)
            ->getJson('/api/v1/employer/opportunities')
            ->assertForbidden();
    }

    public function test_internal_application_and_status_changes_are_split_between_candidate_and_employer(): void
    {
        $employer = User::factory()->create();
        $company = Company::factory()->for($employer, 'owner')->create();
        $opportunity = Opportunity::factory()->create([
            'company_id' => $company->id,
            'employer_name' => $company->name,
            'application_url' => null,
        ]);
        $candidate = User::factory()->create();

        $applicationResponse = $this->actingAs($candidate)
            ->postJson("/api/v1/applications/{$opportunity->slug}", [
                'candidate_message' => 'برای این دوره انگیزه زیادی دارم.',
                'consent_data_sharing' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('application.status', 'applied')
            ->assertJsonPath('application.managed_by_employer', true);

        $applicationId = $applicationResponse->json('application.id');

        $this->actingAs($candidate)
            ->patchJson("/api/v1/applications/{$applicationId}", ['status' => 'offer'])
            ->assertUnprocessable();

        $this->actingAs($employer)
            ->getJson("/api/v1/employer/opportunities/{$opportunity->slug}/applicants")
            ->assertOk()
            ->assertJsonPath('data.0.candidate.email', $candidate->email)
            ->assertJsonPath('data.0.candidate_message', 'برای این دوره انگیزه زیادی دارم.');

        $this->actingAs($employer)
            ->patchJson("/api/v1/employer/applications/{$applicationId}", ['status' => 'reviewing'])
            ->assertOk()
            ->assertJsonPath('application.status', 'reviewing');

        $this->actingAs($candidate)
            ->patchJson("/api/v1/applications/{$applicationId}", ['status' => 'withdrawn'])
            ->assertOk()
            ->assertJsonPath('application.status', 'withdrawn');
    }

    public function test_employer_cannot_access_another_companys_opportunity_or_applicants(): void
    {
        $owner = User::factory()->create();
        $company = Company::factory()->for($owner, 'owner')->create();
        $opportunity = Opportunity::factory()->create(['company_id' => $company->id]);
        $application = Application::query()->create([
            'user_id' => User::factory()->create()->id,
            'opportunity_id' => $opportunity->id,
            'status' => 'applied',
            'applied_at' => now(),
            'data_sharing_consent_at' => now(),
        ]);
        $otherEmployer = User::factory()->create();
        Company::factory()->for($otherEmployer, 'owner')->create();

        $this->actingAs($otherEmployer)
            ->patchJson("/api/v1/employer/opportunities/{$opportunity->slug}", ['status' => 'expired'])
            ->assertNotFound();

        $this->actingAs($otherEmployer)
            ->getJson("/api/v1/employer/opportunities/{$opportunity->slug}/applicants")
            ->assertNotFound();

        $this->actingAs($otherEmployer)
            ->patchJson("/api/v1/employer/applications/{$application->id}", ['status' => 'offer'])
            ->assertNotFound();
    }

    public function test_only_the_owning_employer_can_download_an_applicants_primary_resume(): void
    {
        Storage::fake('local');
        $employer = User::factory()->create();
        $company = Company::factory()->for($employer, 'owner')->create();
        $opportunity = Opportunity::factory()->create(['company_id' => $company->id]);
        $candidate = User::factory()->create();
        $application = Application::query()->create([
            'user_id' => $candidate->id,
            'opportunity_id' => $opportunity->id,
            'status' => 'applied',
            'applied_at' => now(),
            'data_sharing_consent_at' => now(),
        ]);
        Storage::disk('local')->put('resumes/candidate.pdf', 'private resume');
        $resume = Resume::query()->create([
            'user_id' => $candidate->id,
            'original_name' => 'lebenslauf.pdf',
            'disk' => 'local',
            'path' => 'resumes/candidate.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 14,
            'status' => 'uploaded',
            'is_primary' => true,
        ]);
        $application->update(['resume_id' => $resume->id]);

        $this->actingAs($employer)
            ->get("/api/v1/employer/applications/{$application->id}/resume")
            ->assertOk()
            ->assertDownload('lebenslauf.pdf');

        $stranger = User::factory()->create();
        Company::factory()->for($stranger, 'owner')->create();
        $this->actingAs($stranger)
            ->get("/api/v1/employer/applications/{$application->id}/resume")
            ->assertNotFound();
    }

    public function test_company_owner_cannot_apply_to_their_own_opportunity(): void
    {
        $employer = User::factory()->create();
        $company = Company::factory()->for($employer, 'owner')->create();
        $opportunity = Opportunity::factory()->create(['company_id' => $company->id]);

        $this->actingAs($employer)
            ->postJson("/api/v1/applications/{$opportunity->slug}")
            ->assertUnprocessable();
    }
}
