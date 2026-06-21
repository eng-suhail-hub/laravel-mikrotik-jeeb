<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Challenge extends Model
{
    protected $table = 'challenges';

    protected $fillable = [
        'name', 'description', 'is_active', 'starts_at', 'ends_at', 'max_completions',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'max_completions' => 'integer',
    ];

    public function conditions(): HasMany
    {
        return $this->hasMany(ChallengeCondition::class);
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(ChallengeReward::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            });
    }
}
