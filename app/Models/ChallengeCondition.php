<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChallengeCondition extends Model
{
    protected $table = 'challenge_conditions';

    protected $fillable = [
        'challenge_id', 'condition_type', 'operator', 'value', 'logic_group',
    ];

    protected $casts = [
        'value' => 'array',
        'logic_group' => 'integer',
    ];

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(Challenge::class);
    }
}
