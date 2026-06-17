<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ════════════════════════════════════════════════════════════════
 *  موديل العميل (تطبيق Flutter)
 * ════════════════════════════════════════════════════════════════
 *
 *  ⚠️ حسب المواصفات: لا يوجد تسجيل دخول متكرر.
 *  يُستخدم فقط لتخزين الاسم الرباعي ورقم الهاتف للمطابقة مع
 *  إشعار الدفع القادم من محفظة جيب.
 */
class User extends Model
{
    use HasFactory;

    protected $table = 'users';

    protected $fillable = [
        'full_name',
        'phone',
        'device_token',
    ];

    /**
     * ⚠️ لا يوجد حقل password ولا email_verified_at ولا remember_token
     * لذلك لا يُستخدم Authenticatable trait هنا.
     */

    /**
     * تطبيع رقم الهاتف قبل الحفظ
     * - إزالة المسافات والشرطات
     * - إضافة رمز الدولة 967 إذا غاب
     */
    public function setPhoneAttribute(string $value): void
    {
        // إزالة كل ما عدا الأرقام وعلامة +
        $clean = preg_replace('/[^0-9+]/', '', $value);

        // إذا بدأ بـ 7 بدون رمز دولة، أضف 967
        if (preg_match('/^7\d{8}$/', $clean)) {
            $clean = '967' . $clean;
        }

        // إذا بدأ بـ 0، أزل الصفر وأضف 967
        if (preg_match('/^07\d{8}$/', $clean)) {
            $clean = '967' . substr($clean, 1);
        }

        $this->attributes['phone'] = $clean;
    }

    /**
     * كل عمليات الشراء لهذا العميل
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * البحث بـ (الاسم + الهاتف) — يُستخدم في WebhookParser
     */
    public static function findByMatch(string $fullName, string $phone): ?self
    {
        return self::where('full_name', $fullName)
            ->where('phone', $phone)
            ->first();
    }
}
