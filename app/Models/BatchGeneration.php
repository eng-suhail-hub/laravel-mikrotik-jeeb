<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchGeneration extends Model
{
    protected $table = 'batch_generations';

    protected $fillable = ['admin_id', 'profile_id', 'quantity', 'generated_count', 'status', 'generation_config'];

    protected $casts = [
        'quantity' => 'integer',
        'generated_count' => 'integer',
        'generation_config' => 'array',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
