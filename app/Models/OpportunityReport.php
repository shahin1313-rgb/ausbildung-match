<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpportunityReport extends Model
{
    public const REASONS = ['scam', 'broken_link', 'incorrect_info', 'expired', 'other'];

    public const STATUSES = ['pending', 'reviewing', 'resolved', 'dismissed'];

    protected $fillable = [
        'opportunity_id',
        'user_id',
        'reporter_email',
        'reason',
        'details',
        'status',
        'admin_notes',
        'resolved_by',
        'resolved_at',
        'reporter_key',
        'reported_on',
    ];

    protected $hidden = [
        'reporter_key',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
            'reported_on' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (OpportunityReport $report): void {
            if (! $report->isDirty('status')) {
                return;
            }

            if (in_array($report->status, ['resolved', 'dismissed'], true)) {
                $report->resolved_at ??= now();
                $report->resolved_by ??= auth()->id();

                return;
            }

            $report->resolved_at = null;
            $report->resolved_by = null;
        });
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
