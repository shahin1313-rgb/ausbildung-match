<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Source extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'base_url',
        'feed_url',
        'sync_method',
        'field_map',
        'is_enabled',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'field_map' => 'array',
            'is_enabled' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
    }
}
