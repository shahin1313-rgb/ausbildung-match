<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Opportunity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BundesagenturOpportunitySyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_and_updates_ausbildung_opportunities_without_duplicates(): void
    {
        config([
            'services.bundesagentur.enabled' => true,
            'services.bundesagentur.delay_ms' => 0,
            'services.bundesagentur.max_pages' => 1,
            'services.bundesagentur.category_slug' => 'ausbildung',
        ]);

        Category::query()->create([
            'slug' => 'ausbildung',
            'name_fa' => 'دوره‌های آوسبیلدونگ',
            'name_de' => 'Ausbildungsplätze',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Http::fake([
            '*/pc/v6/jobs*' => Http::response([
                'stellenangebote' => [[
                    'referenznummer' => '10000-TEST-S',
                    'titel' => 'Ausbildung Fachinformatiker/in',
                    'arbeitgeber' => 'Example GmbH',
                    'arbeitsort' => ['ort' => 'Berlin', 'region' => 'Berlin'],
                ]],
            ]),
            '*/pc/v4/jobdetails/*' => Http::response([
                'titel' => 'Ausbildung Fachinformatiker/in',
                'arbeitgeber' => 'Example GmbH',
                'stellenbeschreibung' => 'Eine echte Ausbildungsstelle.',
                'arbeitsorte' => [['ort' => 'Berlin', 'region' => 'Berlin']],
                'eintrittsdatum' => '2027-08-01',
                'externeUrl' => 'https://example.test/apply',
            ]),
        ]);

        $this->assertSame(0, Artisan::call('opportunities:sync-bundesagentur', ['--pages' => 1]));
        $this->assertSame(0, Artisan::call('opportunities:sync-bundesagentur', ['--pages' => 1]));

        $this->assertDatabaseCount('opportunities', 1);
        $this->assertDatabaseHas('opportunities', [
            'external_id' => '10000-TEST-S',
            'title_de' => 'Ausbildung Fachinformatiker/in',
            'employer_name' => 'Example GmbH',
            'city' => 'Berlin',
            'status' => 'published',
            'application_url' => 'https://example.test/apply',
        ]);

        $opportunity = Opportunity::query()->firstOrFail();
        $this->assertSame('bundesagentur für arbeit', mb_strtolower($opportunity->source->name));

        Http::assertSent(fn ($request) => $request->hasHeader('X-API-Key', 'jobboerse-jobsuche'));
    }

    public function test_it_does_nothing_when_the_feature_is_disabled(): void
    {
        config(['services.bundesagentur.enabled' => false]);
        Http::fake();

        $this->assertSame(0, Artisan::call('opportunities:sync-bundesagentur'));
        Http::assertNothingSent();
    }
}
