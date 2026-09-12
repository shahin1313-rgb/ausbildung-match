<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin && $this->email_verified_at !== null;
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function resumes(): HasMany
    {
        return $this->hasMany(Resume::class);
    }

    public function favoriteOpportunities(): BelongsToMany
    {
        return $this->belongsToMany(Opportunity::class, 'favorites')->withTimestamps();
    }

    public function germanCv(): HasOne
    {
        return $this->hasOne(GermanCv::class);
    }

    public function applicationClicks(): HasMany
    {
        return $this->hasMany(ApplicationClick::class);
    }
}
