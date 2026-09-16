<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_opening_an_application_link_adds_a_truthful_tracker_entry(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::factory()->create();

        $this->actingAs($user)
            ->postJson("/api/v1/application-clicks/{$opportunity->slug}", ['channel' => 'application_url'])
            ->assertCreated()
            ->assertJsonPath('tracked', true);

        $this->assertDatabaseHas('applications', [
            'user_id' => $user->id,
            'opportunity_id' => $opportunity->id,
            'status' => 'opened',
        ]);

        $this->actingAs($user)
            ->getJson('/api/v1/applications')
            ->assertOk()
            ->assertJsonPath('summary.total', 1)
            ->assertJsonPath('data.0.status', 'opened')
            ->assertJsonPath('data.0.opportunity.id', $opportunity->id);
    }

    public function test_a_user_can_update_only_their_own_application(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $application = Application::query()->create([
            'user_id' => $owner->id,
            'opportunity_id' => Opportunity::factory()->create()->id,
            'status' => 'opened',
        ]);

        $this->actingAs($owner)
            ->patchJson("/api/v1/applications/{$application->id}", ['status' => 'applied'])
            ->assertOk()
            ->assertJsonPath('application.status', 'applied');

        $this->assertNotNull($application->fresh()->applied_at);

        $this->actingAs($otherUser)
            ->patchJson("/api/v1/applications/{$application->id}", ['status' => 'offer'])
            ->assertNotFound();
    }

    public function test_cover_letter_is_saved_in_the_authenticated_users_cv(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->putJson('/api/v1/german-cv', ['cover_letter' => 'Sehr geehrte Damen und Herren'])
            ->assertOk()
            ->assertJsonPath('cv.cover_letter', 'Sehr geehrte Damen und Herren');

        $this->assertDatabaseHas('german_cvs', [
            'user_id' => $user->id,
            'cover_letter' => 'Sehr geehrte Damen und Herren',
        ]);
    }
}
