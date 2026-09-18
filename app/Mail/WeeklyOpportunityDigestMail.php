<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class WeeklyOpportunityDigestMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    public function __construct(
        public readonly Collection $opportunities,
        public readonly string $frontendUrl,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "گزارش هفتگی آوسبیلدونگ ({$this->opportunities->count()} فرصت جدید)",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.weekly-opportunity-digest',
            text: 'emails.weekly-opportunity-digest-text',
        );
    }
}
