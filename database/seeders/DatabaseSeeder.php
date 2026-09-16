<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            SourceSeeder::class,
            AdminUserSeeder::class,
        ]);

        if (! app()->environment('production') && config('seeding.demo_enabled')) {
            $this->call([
                OpportunitySeeder::class,
                DemoUserSeeder::class,
            ]);
        }
    }
}
