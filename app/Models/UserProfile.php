<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'phone',
        'country',
        'birth_date',
        'german_level',
        'education_level',
        'education_title',
        'skills',
        'preferred_category_ids',
        'preferred_cities',
        'work_experience_years',
        'relocation_ready',
        'available_from',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'skills' => 'array',
            'preferred_category_ids' => 'array',
            'preferred_cities' => 'array',
            'work_experience_years' => 'integer',
            'relocation_ready' => 'boolean',
            'available_from' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
