<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserChallenge extends Model
{
    protected $table = 'user_challenges';

    protected $fillable = [
        'user_id', 'challenge_id', 'progress_data', 'started_at',
        'completed_at', 'reward_claimed_at', 'completion_count',
    ];

    protected $casts = [
        'progress_data' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'reward_claimed_at' => 'datetime',
        'completion_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(Challenge::class);
    }
}
