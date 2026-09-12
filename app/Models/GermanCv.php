<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GermanCv extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'headline',
        'summary',
        'contact',
        'experiences',
        'education',
        'skills',
        'languages',
        'certificates',
    ];

    protected function casts(): array
    {
        return [
            'contact' => 'array',
            'experiences' => 'array',
            'education' => 'array',
            'skills' => 'array',
            'languages' => 'array',
            'certificates' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
