<?php

namespace Tests\Feature;

use App\Mail\WeeklyOpportunityDigestMail;
use App\Models\Opportunity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OpportunityLinksAndWeeklyDigestTest extends TestCase
{
    use RefreshDatabase;

    public function test_direct_opportunity_url_serves_the_spa(): void
    {
        $opportunity = Opportunity::factory()->create();

        $this->get("/opportunities/{$opportunity->slug}")
            ->assertOk()
            ->assertViewIs('app');
    }

    public function test_weekly_digest_contains_frontend_opportunity_links(): void
    {
        Mail::fake();
        config()->set('opportunities.weekly_digest_email', 'digest@example.com');
        config()->set('opportunities.frontend_url', 'https://ausbildung.example');
        $opportunity = Opportunity::factory()->create([
            'status' => 'published',
            'published_at' => now()->subDay(),
            'application_deadline' => now()->addMonth(),
        ]);

        $this->artisan('opportunities:weekly-digest')->assertSuccessful();

        Mail::assertQueued(WeeklyOpportunityDigestMail::class, function (WeeklyOpportunityDigestMail $mail) use ($opportunity): bool {
            return $mail->hasTo('digest@example.com')
                && str_contains($mail->render(), "https://ausbildung.example/opportunities/{$opportunity->slug}");
        });
    }
}
