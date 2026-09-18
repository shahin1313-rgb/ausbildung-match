<?php

namespace App\Console\Commands;

use App\Models\Resume;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PurgeExpiredResumes extends Command
{
    protected $signature = 'resumes:purge-expired';

    protected $description = 'Delete resume files and records after their retention period';

    public function handle(): int
    {
        $deleted = 0;

        Resume::query()
            ->whereNotNull('retention_until')
            ->where('retention_until', '<=', now())
            ->chunkById(100, function ($resumes) use (&$deleted): void {
                foreach ($resumes as $resume) {
                    $disk = Storage::disk($resume->disk);
                    if ($disk->exists($resume->path) && ! $disk->delete($resume->path)) {
                        $this->warn("Could not delete stored file for resume {$resume->id}; record retained.");
                        continue;
                    }

                    $resume->delete();
                    $deleted++;
                }
            });

        $this->info("Purged {$deleted} expired resumes.");

        return self::SUCCESS;
    }
}
