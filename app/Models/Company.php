<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    public const STATUSES = ['pending', 'under_review', 'verified', 'suspended'];

    public const VERIFICATION_METHODS = ['admin_review', 'email_domain', 'documents'];

    protected $fillable = [
        'owner_id',
        'name',
        'legal_name',
        'website',
        'contact_email',
        'phone',
        'city',
        'address',
        'description',
        'status',
        'verification_method',
        'verification_notes',
        'verified_at',
        'verified_by',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Company $company): void {
            if (! $company->isDirty('status')) {
                return;
            }

            if ($company->status === 'verified') {
                $company->verification_method ??= 'admin_review';
                $company->verified_at ??= now();
                $company->verified_by ??= auth()->id();

                return;
            }

            $company->verified_at = null;
            $company->verified_by = null;
        });
    }

    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
    }
}
