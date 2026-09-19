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

    public const VERIFICATION_CRITICAL_FIELDS = [
        'legal_name',
        'contact_email',
        'website',
        'address',
    ];

    public const VERIFICATION_LOW_RISK_FIELDS = [
        'name',
        'phone',
        'city',
        'description',
    ];

    /** @var array<string, array{old: mixed, new: mixed}> */
    public array $pendingChangeAudit = [];

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
        static::updating(function (Company $company): void {
            $trackedFields = [
                ...self::VERIFICATION_CRITICAL_FIELDS,
                ...self::VERIFICATION_LOW_RISK_FIELDS,
            ];

            foreach ($trackedFields as $field) {
                if ($company->isDirty($field)) {
                    $company->pendingChangeAudit[$field] = [
                        'old' => $company->getOriginal($field),
                        'new' => $company->getAttribute($field),
                    ];
                }
            }

            $criticalFieldChanged = collect(self::VERIFICATION_CRITICAL_FIELDS)
                ->contains(fn (string $field): bool => $company->isDirty($field));

            if ($criticalFieldChanged && $company->getOriginal('status') === 'verified') {
                $company->status = 'under_review';
                $company->verification_method = null;
                $company->verified_at = null;
                $company->verified_by = null;
            }
        });

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

        static::updated(function (Company $company): void {
            if ($company->pendingChangeAudit !== []) {
                $company->changeHistory()->create([
                    'changed_by' => auth()->id(),
                    'changes' => $company->pendingChangeAudit,
                    'verification_invalidated' => $company->wasChanged('status')
                        && $company->status === 'under_review',
                    'ip_address' => request()->ip(),
                ]);
            }

            if ($company->wasChanged('status') && $company->status === 'under_review') {
                $company->opportunities()
                    ->whereNull('company_review_suspended_at')
                    ->update(['company_review_suspended_at' => now()]);
            }

            if ($company->wasChanged('status') && $company->status === 'verified') {
                $company->opportunities()
                    ->whereNotNull('company_review_suspended_at')
                    ->update(['company_review_suspended_at' => null]);
            }
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

    public function changeHistory(): HasMany
    {
        return $this->hasMany(CompanyChangeHistory::class)->latest();
    }
}
