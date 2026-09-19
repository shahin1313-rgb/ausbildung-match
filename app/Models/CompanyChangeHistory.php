<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyChangeHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'changed_by',
        'changes',
        'verification_invalidated',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'verification_invalidated' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
