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
     * تنسيق مدة الباقة لصيغة مايكروتك (مثلاً: 2w4d)
     */
    public function getFormattedValidityAttribute()
    {
        $hours = $this->duration_hours;
        
        if (empty($hours)) return 'Unlimited';
        
        $w = floor($hours / 168);
        $rem = $hours % 168;
        $d = floor($rem / 24);
        $h = $rem % 24;
        
        $str = '';
        if ($w > 0) $str .= $w . 'w';
        if ($d > 0) $str .= $d . 'd';
        if ($h > 0) $str .= $h . 'h';
        
        return $str !== '' ? $str : '0s';
    }

    /**
     * كل العمليات التي استخدمت هذه الباقة
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
