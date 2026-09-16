<?php

namespace App\Console\Commands;

use App\Mail\WeeklyOpportunityDigestMail;
use App\Models\Opportunity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class WeeklyOpportunityDigest extends Command
{
    protected $signature = 'opportunities:weekly-digest {--email= : Digest recipient}';

    protected $description = 'Email a summary of opportunities published during the last seven days';

    public function handle(): int
    {
        $email = trim((string) ($this->option('email') ?: config('opportunities.weekly_digest_email')));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Set WEEKLY_DIGEST_EMAIL or pass a valid --email address.');

            return self::FAILURE;
        }

        $opportunities = Opportunity::query()
            ->published()
            ->where('published_at', '>=', now()->subDays(7))
            ->latest('published_at')
            ->limit(50)
            ->get();

        Mail::to($email)->send(new WeeklyOpportunityDigestMail(
            $opportunities,
            rtrim((string) config('opportunities.frontend_url'), '/'),
        ));

        $this->info("Weekly digest sent to {$email}.");

        return self::SUCCESS;
    }
}
