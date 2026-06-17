<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ════════════════════════════════════════════════════════════════
 *  موديل السجل الخام (Audit Trail المالي)
 * ════════════════════════════════════════════════════════════════
 *
 *  ⚠️ سجل غير قابل للتعديل (Append-Only).
 *  - لا توجد أعمدة updated_at
 *  - مانع UPDATE/DELETE عبر booted()
 *
 *  هذا الجدول هو المرجع المالي الرسمي، وأي تلاعب به يعني ضياع
 *  إثبات الدفع. الحماية تتم على مستوى الـ Model وقاعدة البيانات.
 */
class RawWebhook extends Model
{
    protected $table = 'raw_webhooks';

    /**
     * ⚠️ $guarded = ['*'] يعني: لا يُسمح بتعديل أي حقل عبر Mass Assignment
     * حتى لو حاول Controller فعل ذلك.
     */
    protected $guarded = ['*'];

    protected $casts = [
        'received_at_ms' => 'integer',
        'parsed_successfully' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * ⚠️ تعطيل updated_at (الجدول لا يحتويه أصلاً)
     */
    public const UPDATED_AT = null;

    /**
     * ⚠️ مانع التعديل والحذف (Append-Only Enforcement)
     */
    protected static function booted(): void
    {
        static::updating(function (self $model): bool {
            // منع التحديث — يمكن تسجيل محاولة في الـ logs
            \Log::warning('Attempt to UPDATE RawWebhook blocked', ['id' => $model->id]);
            return false;
        });

        static::deleting(function (self $model): bool {
            \Log::warning('Attempt to DELETE RawWebhook blocked', ['id' => $model->id]);
            return false;
        });
    }

    /**
     * كل العمليات المرتبطة بهذا الـ Webhook
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * طابع زمني قابل للقراءة من الملي ثانية
     */
    public function getReceivedAtAttribute(): string
    {
        return \Carbon\Carbon::createFromTimestampMs($this->received_at_ms)
            ->format('Y-m-d H:i:s.v');
    }
}
