<?php

namespace App\Console\Commands;

use App\Models\Opportunity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class WeeklyOpportunityDigest extends Command
{
    protected $signature = 'opportunities:weekly-digest {--email= : Digest recipient}';

    protected $description = 'Email a summary of opportunities published during the last seven days';

    public function handle(): int
    {
        $email = trim((string) ($this->option('email') ?: env('WEEKLY_DIGEST_EMAIL', env('ADMIN_EMAIL'))));

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

        $lines = $opportunities->map(fn (Opportunity $opportunity): string => sprintf(
            '• %s — %s (%s) — %s/opportunities/%s',
            $opportunity->title_de,
            $opportunity->employer_name,
            $opportunity->city,
            rtrim(config('app.url'), '/'),
            $opportunity->slug,
        ));

        $body = "فرصت‌های جدید آوسبیلدونگ در ۷ روز گذشته: {$opportunities->count()}\n\n";
        $body .= $lines->isEmpty() ? 'فرصت جدیدی ثبت نشده است.' : $lines->join("\n");

        Mail::raw($body, function ($message) use ($email, $opportunities): void {
            $message->to($email)->subject("گزارش هفتگی آوسبیلدونگ ({$opportunities->count()} فرصت جدید)");
        });

        $this->info("Weekly digest sent to {$email}.");

        return self::SUCCESS;
    }
}
