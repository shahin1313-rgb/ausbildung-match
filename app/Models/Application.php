<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Application extends Model
{
    use HasFactory;

    public const STATUSES = ['opened', 'applied', 'reviewing', 'interview', 'offer', 'rejected', 'withdrawn'];

    protected $fillable = [
        'user_id',
        'opportunity_id',
        'resume_id',
        'status',
        'applied_at',
        'interview_at',
        'notes',
        'candidate_message',
        'data_sharing_consent_at',
    ];

    protected function casts(): array
    {
        return [
            'applied_at' => 'datetime',
            'interview_at' => 'datetime',
            'data_sharing_consent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class);
    }
}
