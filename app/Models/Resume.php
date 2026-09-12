<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Resume extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'original_name',
        'disk',
        'path',
        'mime_type',
        'size_bytes',
        'status',
        'is_primary',
        'extracted_data',
        'analyzed_at',
    ];

    protected $hidden = [
        'disk',
        'path',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'is_primary' => 'boolean',
            'extracted_data' => 'array',
            'analyzed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
