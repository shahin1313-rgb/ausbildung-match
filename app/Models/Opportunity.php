<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Opportunity extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'category_id',
        'source_id',
        'company_id',
        'external_id',
        'slug',
        'title_fa',
        'title_de',
        'employer_name',
        'description_fa',
        'description_de',
        'city',
        'state',
        'training_type',
        'start_date',
        'application_deadline',
        'monthly_salary_from',
        'monthly_salary_to',
        'required_german_level',
        'education_requirement',
        'skills',
        'accepts_international',
        'visa_support',
        'application_url',
        'contact_email',
        'status',
        'company_review_suspended_at',
        'published_at',
        'source_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'skills' => 'array',
            'accepts_international' => 'boolean',
            'start_date' => 'date',
            'application_deadline' => 'date',
            'company_review_suspended_at' => 'datetime',
            'published_at' => 'datetime',
            'source_updated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Opportunity $opportunity): void {
            if ($opportunity->slug && ! $opportunity->isDirty('title_de')) {
                return;
            }

            $base = Str::slug($opportunity->title_de ?: $opportunity->title_fa) ?: 'opportunity';
            $slug = $base;
            $suffix = 2;

            while (static::withTrashed()
                ->where('slug', $slug)
                ->when($opportunity->exists, fn (Builder $query) => $query->where('id', '!=', $opportunity->getKey()))
                ->exists()) {
                $slug = $base.'-'.$suffix++;
            }

            $opportunity->slug = $slug;
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', 'published')
            ->whereNull('company_review_suspended_at')
            ->where(function (Builder $query): void {
                $query->whereNull('company_id')
                    ->orWhereHas('company', fn (Builder $company): Builder => $company->where('status', 'verified'));
            })
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('application_deadline')
                    ->orWhereDate('application_deadline', '>=', today());
            });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function favoredByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    public function applicationClicks(): HasMany
    {
        return $this->hasMany(ApplicationClick::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(OpportunityReport::class);
    }
}
