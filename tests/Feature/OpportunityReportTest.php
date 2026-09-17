<?php

namespace Tests\Feature;

use App\Models\Opportunity;
use App\Models\OpportunityReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpportunityReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_report_a_published_opportunity_without_storing_raw_ip(): void
    {
        $opportunity = Opportunity::factory()->create();

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.10'])
            ->postJson("/api/v1/opportunities/{$opportunity->slug}/reports", [
                'reason' => 'broken_link',
                'reporter_email' => 'reporter@example.com',
            ])
            ->assertCreated()
            ->assertJsonPath('report.status', 'pending');

        $report = OpportunityReport::query()->sole();

        $this->assertSame('reporter@example.com', $report->reporter_email);
        $this->assertSame('broken_link', $report->reason);
        $this->assertSame(64, strlen($report->reporter_key));
        $this->assertStringNotContainsString('198.51.100.10', $report->reporter_key);
    }

    public function test_authenticated_report_uses_account_identity_and_email(): void
    {
        $user = User::factory()->create(['email' => 'member@example.com']);
        $opportunity = Opportunity::factory()->create();

        $this->actingAs($user)
            ->postJson("/api/v1/opportunities/{$opportunity->slug}/reports", [
                'reason' => 'expired',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('opportunity_reports', [
            'opportunity_id' => $opportunity->id,
            'user_id' => $user->id,
            'reporter_email' => 'member@example.com',
            'reason' => 'expired',
            'status' => 'pending',
        ]);
    }

    public function test_guest_report_validation_requires_email_and_context_for_complex_reasons(): void
    {
        $opportunity = Opportunity::factory()->create();

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.11'])
            ->postJson("/api/v1/opportunities/{$opportunity->slug}/reports", [
                'reason' => 'scam',
                'details' => 'کوتاه',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['details', 'reporter_email']);

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.12'])
            ->postJson("/api/v1/opportunities/{$opportunity->slug}/reports", [
                'reason' => 'unsupported',
                'reporter_email' => 'reporter@example.com',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['reason']);
    }

    public function test_same_reporter_cannot_report_the_same_opportunity_twice_in_one_day(): void
    {
        $opportunity = Opportunity::factory()->create();
        $payload = [
            'reason' => 'broken_link',
            'reporter_email' => 'duplicate@example.com',
        ];

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.13'])
            ->postJson("/api/v1/opportunities/{$opportunity->slug}/reports", $payload)
            ->assertCreated();

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.13'])
            ->postJson("/api/v1/opportunities/{$opportunity->slug}/reports", $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['report']);

        $this->assertDatabaseCount('opportunity_reports', 1);
    }

    public function test_draft_opportunities_cannot_be_reported_through_public_api(): void
    {
        $opportunity = Opportunity::factory()->draft()->create();

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.14'])
            ->postJson("/api/v1/opportunities/{$opportunity->slug}/reports", [
                'reason' => 'broken_link',
                'reporter_email' => 'reporter@example.com',
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('opportunity_reports', 0);
    }

    public function test_report_endpoint_is_rate_limited(): void
    {
        $opportunity = Opportunity::factory()->create();
        $endpoint = "/api/v1/opportunities/{$opportunity->slug}/reports";
        $payload = ['reason' => 'broken_link', 'reporter_email' => 'rate@example.com'];
        $client = $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.15']);

        $client->postJson($endpoint, $payload)->assertCreated();
        $client->postJson($endpoint, $payload)->assertUnprocessable();
        $client->postJson($endpoint, $payload)->assertUnprocessable();
        $client->postJson($endpoint, $payload)->assertStatus(429);
    }

    public function test_resolving_a_report_records_admin_and_resolution_time(): void
    {
        $admin = User::factory()->admin()->create();
        $report = OpportunityReport::query()->create([
            'opportunity_id' => Opportunity::factory()->create()->id,
            'reporter_email' => 'reporter@example.com',
            'reason' => 'incorrect_info',
            'details' => 'حقوق نوشته‌شده با منبع اصلی مطابقت ندارد.',
            'status' => 'pending',
            'reporter_key' => hash('sha256', 'reporter'),
            'reported_on' => today(),
        ]);

        $this->actingAs($admin);
        $report->update([
            'status' => 'resolved',
            'admin_notes' => 'اطلاعات آگهی اصلاح شد.',
        ]);

        $report->refresh();
        $this->assertSame($admin->id, $report->resolved_by);
        $this->assertNotNull($report->resolved_at);
        $this->assertSame('اطلاعات آگهی اصلاح شد.', $report->admin_notes);
    }
}
