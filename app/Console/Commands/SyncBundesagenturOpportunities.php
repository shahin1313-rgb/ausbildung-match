<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Opportunity;
use App\Models\Source;
use App\Services\BundesagenturJobsClient;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Throwable;

class SyncBundesagenturOpportunities extends Command
{
    protected $signature = 'opportunities:sync-bundesagentur
        {--pages= : Maximum number of result pages}
        {--location= : Optional German city or postal code}';

    protected $description = 'Synchronize Ausbildung opportunities from the Bundesagentur Jobsuche API';

    public function handle(BundesagenturJobsClient $client): int
    {
        if (! config('services.bundesagentur.enabled')) {
            $this->warn('Bundesagentur synchronization is disabled.');

            return self::SUCCESS;
        }

        $category = Category::query()
            ->where('slug', (string) config('services.bundesagentur.category_slug', 'ausbildung'))
            ->first();

        if (! $category) {
            $this->error('Configured fallback category does not exist. Run the category seeder first.');

            return self::FAILURE;
        }

        $source = Source::query()->updateOrCreate(
            ['name' => 'Bundesagentur für Arbeit'],
            [
                'base_url' => 'https://www.arbeitsagentur.de/jobsuche/',
                'feed_url' => (string) config('services.bundesagentur.base_url'),
                'sync_method' => 'api',
                'is_enabled' => true,
            ],
        );

        $pages = max(1, min(20, (int) ($this->option('pages') ?: config('services.bundesagentur.max_pages', 3))));
        $location = trim((string) $this->option('location')) ?: null;
        $created = 0;
        $updated = 0;
        $failed = 0;

        for ($page = 1; $page <= $pages; $page++) {
            try {
                $payload = $client->searchAusbildung($page, $location);
            } catch (Throwable $exception) {
                report($exception);
                $this->error("Search request failed on page {$page}: {$exception->getMessage()}");

                return self::FAILURE;
            }

            $rows = data_get($payload, 'stellenangebote')
                ?? data_get($payload, '_embedded.stellenangebote')
                ?? data_get($payload, 'jobs')
                ?? [];

            if (! is_array($rows) || $rows === []) {
                break;
            }

            foreach ($rows as $row) {
                $reference = trim((string) (data_get($row, 'referenznummer') ?? data_get($row, 'refnr')));

                if ($reference === '') {
                    $failed++;
                    continue;
                }

                try {
                    $details = $client->details($reference);
                    $data = $this->mapOpportunity($details, $row, $reference);

                    $opportunity = Opportunity::withTrashed()->firstOrNew([
                        'source_id' => $source->id,
                        'external_id' => $reference,
                    ]);
                    $wasNew = ! $opportunity->exists;

                    if ($opportunity->exists && $opportunity->trashed()) {
                        $opportunity->restore();
                    }
                    $opportunity->fill([
                        ...$data,
                        'category_id' => $category->id,
                        'source_id' => $source->id,
                        'external_id' => $reference,
                        'status' => 'published',
                        'published_at' => $opportunity->published_at ?: now(),
                        'source_updated_at' => now(),
                    ])->save();

                    $wasNew ? $created++ : $updated++;
                } catch (Throwable $exception) {
                    report($exception);
                    $failed++;
                    $this->warn("Failed to import {$reference}: {$exception->getMessage()}");
                }

                usleep(max(0, (int) config('services.bundesagentur.delay_ms', 250)) * 1000);
            }
        }

        $source->forceFill(['last_synced_at' => now()])->save();
        $this->info("Bundesagentur sync complete. Created: {$created}, updated: {$updated}, failed: {$failed}.");

        return $failed > 0 && ($created + $updated) === 0 ? self::FAILURE : self::SUCCESS;
    }

    private function mapOpportunity(array $details, array $summary, string $reference): array
    {
        $title = trim((string) (
            data_get($details, 'titel')
            ?? data_get($summary, 'titel')
            ?? 'Ausbildungsplatz'
        ));
        $employer = trim((string) (
            data_get($details, 'arbeitgeber')
            ?? data_get($summary, 'arbeitgeber')
            ?? 'Nicht angegeben'
        ));
        $description = trim((string) (
            data_get($details, 'stellenbeschreibung')
            ?? data_get($details, 'beschreibung')
            ?? $title
        ));
        $workplace = Arr::first((array) (
            data_get($details, 'arbeitsorte')
            ?? [data_get($summary, 'arbeitsort', [])]
        ), fn ($value) => is_array($value), []);
        $city = trim((string) (
            data_get($workplace, 'ort')
            ?? data_get($summary, 'arbeitsort.ort')
            ?? 'Deutschland'
        ));
        $state = data_get($workplace, 'region')
            ?? data_get($summary, 'arbeitsort.region');

        $externalUrl = data_get($details, 'externeUrl')
            ?? data_get($details, 'stellenangebotsart.externeUrl');

        return [
            'title_de' => $title,
            // Until editorial translation is available, retain the original instead of inventing Persian copy.
            'title_fa' => $title,
            'employer_name' => $employer,
            'description_de' => $description,
            'description_fa' => $description,
            'city' => $city,
            'state' => $state ? trim((string) $state) : null,
            'training_type' => 'dual',
            'start_date' => $this->date(data_get($details, 'eintrittsdatum') ?? data_get($summary, 'eintrittsdatum')),
            'application_deadline' => $this->date(data_get($details, 'befristungDatum')),
            'required_german_level' => 'b1',
            'accepts_international' => false,
            'visa_support' => 'unknown',
            'application_url' => filter_var($externalUrl, FILTER_VALIDATE_URL)
                ? $externalUrl
                : 'https://www.arbeitsagentur.de/jobsuche/jobdetail/'.rawurlencode($reference),
            'contact_email' => null,
        ];
    }

    private function date(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }
}
