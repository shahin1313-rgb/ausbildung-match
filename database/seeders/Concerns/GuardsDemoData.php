<?php

namespace Database\Seeders\Concerns;

use LogicException;

trait GuardsDemoData
{
    protected function ensureDemoDataIsAllowed(): void
    {
        if (app()->environment('production')) {
            throw new LogicException('Demo data seeding is forbidden in production.');
        }

        if (! config('seeding.demo_enabled')) {
            throw new LogicException('Demo data seeding is disabled. Set SEED_DEMO_DATA=true outside production to enable it.');
        }
    }
}
