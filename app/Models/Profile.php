<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ════════════════════════════════════════════════════════════════
 *  موديل الباقات
 * ════════════════════════════════════════════════════════════════
 *
 *  كل صف = باقة معروضة في Flutter، مرتبطة بـ Profile في User Manager.
 */
class Profile extends Model
{
    use HasFactory;

    protected $table = 'profiles';

    protected $fillable = [
        'name',
        'mikrotik_profile_name',
        'price',
        'duration_hours',
        'speed_limit',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration_hours' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * الباقات النشطة فقط (للعرض في Flutter)
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * كل العمليات التي استخدمت هذه الباقة
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
