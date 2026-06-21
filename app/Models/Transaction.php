<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ════════════════════════════════════════════════════════════════
 *  موديل العمليات (Transactions)
 * ════════════════════════════════════════════════════════════════
 *
 *  يمثل دورة حياة طلب شراء كرت من بدايته إلى نهايته.
 */
class Transaction extends Model
{
    use HasFactory;

    protected $table = 'transactions';

    protected $fillable = [
        'user_id',
        'profile_id',
        'raw_webhook_id',
        'webhook_phone',
        'webhook_full_name',
        'webhook_amount',
        'jeeb_reference',
        'mikrotik_username',
        'mikrotik_password',
        'card_generated_at',
        'status',
        'failure_reason',
        'activated_by_admin_id',
        // V2 columns
        'type',
        'verification_status',
        'points_amount',
        'points_before',
        'points_after',
        'auto_revoke_at',
        'revoked_at',
        'revoke_job_dispatched',
    ];

    protected $casts = [
        'webhook_amount' => 'decimal:2',
        'card_generated_at' => 'datetime',
        'points_amount' => 'decimal:2',
        'points_before' => 'decimal:2',
        'points_after' => 'decimal:2',
        'auto_revoke_at' => 'datetime',
        'revoked_at' => 'datetime',
        'revoke_job_dispatched' => 'boolean',
    ];

    // الحالات المسموحة (للتحقق في الـ Controller)
    public const STATUS_PENDING_MATCH = 'pending_match';

    public const STATUS_MATCHED = 'matched';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_MANUAL_PENDING = 'manual_pending';

    // V2 type constants
    public const TYPE_CARD_PURCHASE = 'card_purchase';

    public const TYPE_POINTS_RECHARGE = 'points_recharge';

    // V2 verification status constants
    public const VERIFICATION_PENDING = 'pending_verification';

    public const VERIFICATION_VERIFIED = 'verified';

    public const VERIFICATION_REVOKED = 'revoked';

    /**
     * العلاقات
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withDefault();
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function rawWebhook(): BelongsTo
    {
        return $this->belongsTo(RawWebhook::class, 'raw_webhook_id');
    }

    public function activatedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'activated_by_admin_id');
    }

    /**
     * Scopes للاستخدام في لوحة الأدمن
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING_MATCH,
            self::STATUS_MANUAL_PENDING,
        ]);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * اسم الحالة بالعربية (للعرض في الواجهة)
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING_MATCH => 'بانتظار المطابقة',
            self::STATUS_MATCHED => 'تمت المطابقة',
            self::STATUS_PROCESSING => 'قيد التوليد',
            self::STATUS_COMPLETED => 'مكتملة',
            self::STATUS_FAILED => 'فشلت',
            self::STATUS_MANUAL_PENDING => 'بانتظار التفعيل اليدوي',
            default => $this->status,
        };
    }
}
