<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ════════════════════════════════════════════════════════════════
 *  موديل إعدادات الراوتر (singleton)
 * ════════════════════════════════════════════════════════════════
 *
 *  يحتوي صف واحد فقط (id=1 عادةً).
 *  كلمة مرور الراوتر مُشفّرة عبر encrypted cast (لا تظهر في النص).
 */
class RouterSetting extends Model
{
    protected $table = 'router_settings';

    protected $fillable = [
        'host',
        'port',
        'username',
        'password',
        'router_identity',
        'routeros_version',
        'board_name',
        'is_connected',
        'last_test_at',
    ];

    protected $casts = [
        'port' => 'integer',
        'is_connected' => 'boolean',
        'last_test_at' => 'datetime',
        // ⚠️ تشفير كلمة مرور الراوتر في قاعدة البيانات
        'password' => 'encrypted',
    ];

    /**
     * الحصول على الإعدادات الحالية (Singleton)
     */
    public static function current(): self
    {
        $setting = self::first();

        // إذا لم يوجد سجل، أنشئ سجل افتراضي
        if (!$setting) {
            $setting = self::create([
                'host' => '0.0.0.0',
                'port' => config('mikrotik.default_port'),
                'username' => 'admin',
                'password' => '',
                'is_connected' => false,
            ]);
        }

        return $setting;
    }

    /**
     * هل الإعدادات مكتملة بما يكفي لمحاولة الاتصال؟
     */
    public function isConnectable(): bool
    {
        return !empty($this->host)
            && !empty($this->username)
            && !empty($this->password)
            && $this->host !== '0.0.0.0';
    }
}
