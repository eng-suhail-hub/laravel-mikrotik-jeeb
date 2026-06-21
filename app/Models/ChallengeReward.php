<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChallengeReward extends Model
{
    protected $table = 'challenge_rewards';

    protected $fillable = [
        'challenge_id', 'reward_type', 'value', 'priority',
    ];

    protected $casts = [
        'value' => 'array',
        'priority' => 'integer',
    ];

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(Challenge::class);
    }
}
