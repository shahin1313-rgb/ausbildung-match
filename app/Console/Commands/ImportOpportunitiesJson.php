<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Opportunity;
use App\Models\Source;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use JsonException;
use Throwable;

class ImportOpportunitiesJson extends Command
{
    protected $signature = 'opportunities:import-json {file : Absolute or project-relative JSON path} {--source= : Existing source name}';

    protected $description = 'Import normalized Ausbildung opportunities from a local JSON file';

    public function handle(): int
    {
        $path = (string) $this->argument('file');
        $path = str_starts_with($path, DIRECTORY_SEPARATOR) ? $path : base_path($path);
        $sourceName = trim((string) $this->option('source'));
        $source = Source::query()->where('name', $sourceName)->first();

        if (! $source) {
            $this->error('Source not found. Pass an existing source with --source="Source name".');

            return self::FAILURE;
        }

        if (! is_file($path) || ! is_readable($path)) {
            $this->error("JSON file is not readable: {$path}");

            return self::FAILURE;
        }

        try {
            $rows = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            $this->error('Invalid JSON: '.$exception->getMessage());

            return self::FAILURE;
        }

        if (! is_array($rows) || ! array_is_list($rows)) {
            $this->error('The JSON root must be an array of opportunity objects.');

            return self::FAILURE;
        }

        $created = 0;
        $updated = 0;
        $failed = 0;

        foreach ($rows as $index => $row) {
            $validator = Validator::make(is_array($row) ? $row : [], [
                'external_id' => ['required', 'string', 'max:255'],
                'category_slug' => ['required', 'string', 'exists:categories,slug'],
                'title_fa' => ['required', 'string', 'max:255'],
                'title_de' => ['required', 'string', 'max:255'],
                'employer_name' => ['required', 'string', 'max:255'],
                'description_fa' => ['required', 'string'],
                'description_de' => ['nullable', 'string'],
                'city' => ['required', 'string', 'max:255'],
                'state' => ['nullable', 'string', 'max:255'],
                'training_type' => ['nullable', 'in:dual,school'],
                'start_date' => ['nullable', 'date'],
                'application_deadline' => ['nullable', 'date'],
                'monthly_salary_from' => ['nullable', 'integer', 'min:0'],
                'monthly_salary_to' => ['nullable', 'integer', 'min:0'],
                'required_german_level' => ['nullable', 'in:a2,b1,b2,c1'],
                'education_requirement' => ['nullable', 'string', 'max:255'],
                'skills' => ['nullable', 'array'],
                'skills.*' => ['string', 'max:80'],
                'accepts_international' => ['nullable', 'boolean'],
                'visa_support' => ['nullable', 'in:unknown,no,possible,yes'],
                'application_url' => ['required', 'url', 'max:2000'],
                'contact_email' => ['nullable', 'email', 'max:255'],
                'source_updated_at' => ['nullable', 'date'],
            ]);

            if ($validator->fails()) {
                $failed++;
                $this->warn('Row '.($index + 1).': '.$validator->errors()->first());
                continue;
            }

            try {
                $data = $validator->validated();
                $category = Category::query()->where('slug', $data['category_slug'])->firstOrFail();
                unset($data['category_slug']);
                $data = [
                    ...$data,
                    'category_id' => $category->id,
                    'source_id' => $source->id,
                    'slug' => Str::slug($data['title_de'].'-'.$data['external_id']),
                    'training_type' => $data['training_type'] ?? 'dual',
                    'required_german_level' => $data['required_german_level'] ?? 'b1',
                    'accepts_international' => $data['accepts_international'] ?? false,
                    'visa_support' => $data['visa_support'] ?? 'unknown',
                    'status' => 'published',
                    'published_at' => now(),
                ];

                $opportunity = Opportunity::query()->firstOrNew([
                    'source_id' => $source->id,
                    'external_id' => $data['external_id'],
                ]);
                $wasNew = ! $opportunity->exists;
                $opportunity->fill($data)->save();
                $wasNew ? $created++ : $updated++;
            } catch (Throwable $exception) {
                $failed++;
                $this->warn('Row '.($index + 1).': '.$exception->getMessage());
            }
        }

        $source->forceFill(['last_synced_at' => now()])->save();
        $this->info("Import complete. Created: {$created}, updated: {$updated}, failed: {$failed}.");

        return $failed > 0 ? self::INVALID : self::SUCCESS;
    }
}
