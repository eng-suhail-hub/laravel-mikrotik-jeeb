<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceLog extends Model
{
    protected $table = 'maintenance_logs';

    protected $fillable = ['admin_id', 'action', 'status', 'raw_output'];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
