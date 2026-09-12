<?php

namespace App\Console\Commands;

use App\Models\Opportunity;
use Illuminate\Console\Command;

class ExpireOpportunities extends Command
{
    protected $signature = 'opportunities:expire';

    protected $description = 'Mark opportunities with a past application deadline as expired';

    public function handle(): int
    {
        $count = Opportunity::query()
            ->where('status', 'published')
            ->whereDate('application_deadline', '<', today())
            ->update(['status' => 'expired']);

        $this->info("Expired {$count} opportunities.");

        return self::SUCCESS;
    }
}
