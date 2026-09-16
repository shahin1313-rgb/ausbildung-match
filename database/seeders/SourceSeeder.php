<?php

namespace Database\Seeders;

use App\Models\Source;
use Illuminate\Database\Seeder;

class SourceSeeder extends Seeder
{
    public function run(): void
    {
        $sources = [
            ['name' => 'Bundesagentur für Arbeit', 'base_url' => 'https://www.arbeitsagentur.de/jobsuche/', 'sync_method' => 'manual'],
            ['name' => 'IHK Lehrstellenbörse', 'base_url' => 'https://www.ihk-lehrstellenboerse.de/', 'sync_method' => 'manual'],
            ['name' => 'Lehrstellenradar (HWK)', 'base_url' => 'https://www.lehrstellen-radar.de/', 'sync_method' => 'manual'],
        ];

        if (! app()->environment('production') && config('seeding.demo_enabled')) {
            $sources[] = ['name' => 'Ausbildung Match Demo', 'base_url' => 'https://example.test/opportunities/', 'sync_method' => 'manual'];
        }

        foreach ($sources as $source) {
            Source::query()->updateOrCreate(['name' => $source['name']], [...$source, 'is_enabled' => true]);
        }
    }
}
