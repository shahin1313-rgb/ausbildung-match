<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Company;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyReverificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_critical_change_invalidates_verification_and_hides_opportunity(): void
    {
        $owner = User::factory()->create();
        $company = Company::factory()->for($owner, 'owner')->create(['status' => 'verified']);
        $opportunity = Opportunity::factory()->for($company)->for(Category::factory())->create([
            'status' => 'published',
            'application_deadline' => now()->addMonth(),
        ]);

        $payload = $company->only([
            'name', 'legal_name', 'website', 'contact_email', 'phone', 'city', 'address', 'description',
        ]);
        $payload['legal_name'] = 'Changed Legal GmbH';

        $this->actingAs($owner)
            ->putJson('/api/v1/employer/company', $payload)
            ->assertOk()
            ->assertJsonPath('company.status', 'under_review');

        $this->assertDatabaseHas('company_change_histories', [
            'company_id' => $company->id,
            'changed_by' => $owner->id,
            'verification_invalidated' => true,
        ]);
        $this->assertNotNull($opportunity->fresh()->company_review_suspended_at);
        $this->getJson('/api/v1/opportunities/'.$opportunity->slug)->assertNotFound();
    }

    public function test_low_risk_change_is_audited_without_invalidating_verification(): void
    {
        $owner = User::factory()->create();
        $company = Company::factory()->for($owner, 'owner')->create(['status' => 'verified']);
        $payload = $company->only([
            'name', 'legal_name', 'website', 'contact_email', 'phone', 'city', 'address', 'description',
        ]);
        $payload['description'] = 'Updated public description';

        $this->actingAs($owner)
            ->putJson('/api/v1/employer/company', $payload)
            ->assertOk()
            ->assertJsonPath('company.status', 'verified');

        $this->assertDatabaseHas('company_change_histories', [
            'company_id' => $company->id,
            'verification_invalidated' => false,
        ]);
    }

    public function test_admin_reverification_restores_company_opportunities(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $company = Company::factory()->for($owner, 'owner')->create(['status' => 'verified']);
        $opportunity = Opportunity::factory()->for($company)->for(Category::factory())->create([
            'status' => 'published',
            'application_deadline' => now()->addMonth(),
        ]);

        $company->update(['contact_email' => 'new@example.de']);
        $this->assertNotNull($opportunity->fresh()->company_review_suspended_at);

        $this->actingAs($admin);
        $company->update([
            'status' => 'verified',
            'verification_method' => 'admin_review',
        ]);

        $this->assertNull($opportunity->fresh()->company_review_suspended_at);
        $this->getJson('/api/v1/opportunities/'.$opportunity->slug)->assertOk();
    }
}
