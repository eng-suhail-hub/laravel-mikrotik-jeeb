<?php

namespace App\Services;

use App\Models\RouterSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RouterOS\Exceptions\ConnectException;
use RouterOS\Exceptions\QueryException;
use RouterOS\Client;
use Throwable;

/**
 * ════════════════════════════════════════════════════════════════
 *  كلاس خدمة المايكروتك (MikroTik Service)
 * ════════════════════════════════════════════════════════════════
 *
 *  ⚠️ هذا الكلاس هو الجسر الوحيد بين Laravel والراوتر.
 *  مسؤولياته:
 *  1. فتح/إغلاق اتصال مع RouterOS v6 عبر منفذ 8728
 *  2. استخدام Laravel Cache::lock لمنع أكثر من اتصال متزامن
 *     (يقلل استهلاك المعالج على الراوتر PowerPC)
 *  3. تنفيذ الأوامر على User Manager فقط (ليس Hotspot)
 *  4. رمي استثناءات واضحة يمكن للـ Job التعامل معها
 *
 *  ⚠️ لا يستخدم RouterOS v7 REST API — غير متوافق مع v6.49.19
 *  ⚠️ يستخدم مكتبة routeros-api المتوافقة مع v6
 *
 *  ⚠️ هذا الكلاس منفصل تماماً عن WebhookParser (SoC).
 */
class MikroTikService
{
    private ?Client $client = null;
    private bool $lockAcquired = false;

    /**
     * الاتصال بالراوتر مع استخدام Lock
     *
     * @throws ConnectException إذا فشل الاتصال
     */
    public function connect(): bool
    {
        if ($this->client !== null) {
            return true; // متصل بالفعل
        }

        $setting = RouterSetting::current();

        if (!$setting->isConnectable()) {
            throw new \RuntimeException(
                'بيانات الراوتر غير مكتملة. يرجى إدخال IP/Username/Password من لوحة الأدمن.'
            );
        }

        // ⚠️ استخدام Laravel Cache Lock لمنع أكثر من اتصال متزامن
        // مفتاح القفل مأخوذ من config (يمكن تغييره لـ environment مختلف)
        $lockKey = config('mikrotik.queue_lock_key', 'mikrotik_connection_lock');
        $lockTtl = config('mikrotik.queue_lock_ttl', 30);

        $lock = Cache::lock($lockKey, $lockTtl);

        // block: false = لا تنتظر، إذا كان مقفلاً ارمِ استثناء
        // (يمنع تراكم الاتصالات عند انقطاع الراوتر)
        $this->lockAcquired = $lock->get();

        if (!$this->lockAcquired) {
            throw new \RuntimeException(
                'هناك عملية أخرى قيد التنفيذ مع الراوتر. حاول لاحقاً.'
            );
        }

        try {
            $this->client = new Client([
                'host' => $setting->host,
                'port' => (int) $setting->port,
                'user' => $setting->username,
                'pass' => $setting->password,
                'timeout' => (int) config('mikrotik.connection_timeout', 10),
            ]);

            // اختبار الاتصال بأمر بسيط
            $this->client->query('/system/identity/print');

            // تحديث حالة الاتصال في قاعدة البيانات
            $setting->update([
                'is_connected' => true,
                'last_test_at' => now(),
            ]);

            Log::info('MikroTik connection established', [
                'host' => $setting->host,
                'port' => $setting->port,
            ]);

            return true;

        } catch (ConnectException $e) {
            $this->releaseLock();
            $setting->update(['is_connected' => false]);
            Log::error('MikroTik connection failed', ['error' => $e->getMessage()]);
            throw $e;
        } catch (Throwable $e) {
            $this->releaseLock();
            $setting->update(['is_connected' => false]);
            Log::error('MikroTik unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * إغلاق الاتصال وتحرير القفل
     */
    public function disconnect(): void
    {
        // Client في routeros-api لا يحتاج close() صريح — يُغلق عند unset
        $this->client = null;
        $this->releaseLock();
    }

    /**
     * تحرير الـ Lock بشكل آمن
     */
    private function releaseLock(): void
    {
        if ($this->lockAcquired) {
            try {
                Cache::lock(config('mikrotik.queue_lock_key', 'mikrotik_connection_lock'))->release();
            } catch (Throwable $e) {
                // تجاهل أخطاء التحرير (قد يكون المفتاح انتهت صلاحيته)
            }
            $this->lockAcquired = false;
        }
    }

    /**
     * ⚠️ حسب المواصفات: إنشاء مستخدم في User Manager فقط
     *
     * @param string $username اسم المستخدم
     * @param string $password كلمة المرور
     * @param string $mikrotikProfileName اسم الباقة في User Manager
     * @return bool نجاح العملية
     */
    public function createUserManagerUser(
        string $username,
        string $password,
        string $mikrotikProfileName
    ): bool {
        if ($this->client === null) {
            $this->connect();
        }

        try {
            // ⚠️ المسار الصحيح: /tool/user-manager/user/add
            // ليس /ip/hotspot/user/add (Hotspot ممنوح حسب المواصفات)
            $this->client->query(
                config('mikrotik.user_manager_add_path', '/tool/user-manager/user/add'),
                [
                    'name' => $username,
                    'password' => $password,
                    'customer' => 'admin',
                ]
            )->read();

            // ربط المستخدم بالـ Profile في User Manager
            // المُعرّف يتم جلبه تلقائياً في أمر set عبر name
            $this->client->query(
                '/tool/user-manager/user/profile/set',
                [
                    'user' => $username,
                    'profile' => $mikrotikProfileName,
                ]
            )->read();

            Log::info('User Manager user created', [
                'username' => $username,
                'profile' => $mikrotikProfileName,
            ]);

            return true;

        } catch (QueryException $e) {
            // إذا كان المستخدم موجوداً مسبقاً (نفس الاسم)، هذا خطأ متوقع
            if (str_contains($e->getMessage(), 'already have')) {
                Log::warning('User already exists in User Manager', [
                    'username' => $username,
                ]);
                throw new \RuntimeException("اسم المستخدم {$username} موجود مسبقاً في الراوتر.");
            }
            throw $e;
        }
    }

    /**
     * اختبار الاتصال (يُستخدم من لوحة الأدمن قبل الحفظ)
     *
     * @param string $host
     * @param int $port
     * @param string $username
     * @param string $password
     * @return array ['success' => bool, 'info' => array, 'error' => string|null]
     */
    public function testConnection(
        string $host,
        int $port,
        string $username,
        string $password
    ): array {
        try {
            $client = new Client([
                'host' => $host,
                'port' => $port,
                'user' => $username,
                'pass' => $password,
                'timeout' => (int) config('mikrotik.connection_timeout', 10),
            ]);

            // جلب هوية الراوتر + إصدار النظام + اسم اللوحة
            $identityResponse = $client->query('/system/identity/print')->read();
            $resourceResponse = $client->query('/system/resource/print')->read();

            $identity = $identityResponse[0]['name'] ?? 'unknown';
            $version = $resourceResponse[0]['version'] ?? 'unknown';
            $board = $resourceResponse[0]['board-name'] ?? 'unknown';

            return [
                'success' => true,
                'info' => [
                    'identity' => $identity,
                    'version' => $version,
                    'board' => $board,
                ],
                'error' => null,
            ];

        } catch (Throwable $e) {
            return [
                'success' => false,
                'info' => null,
                'error' => $this->translateError($e->getMessage()),
            ];
        }
    }

    /**
     * ترجمة رسائل خطأ MikroTik للعربية (للعرض في الواجهة)
     */
    private function translateError(string $message): string
    {
        return match (true) {
            str_contains($message, 'Connection refused') => 'الراوتر يرفض الاتصال. تحقق من الـ IP والمنفذ.',
            str_contains($message, 'timeout')            => 'انتهت مهلة الاتصال. الراوتر لا يستجيب.',
            str_contains($message, 'Authentication')     => 'بيانات الدخول (اسم المستخدم/كلمة المرور) غير صحيحة.',
            str_contains($message, 'Could not resolve')  => 'تعذّر الوصول لعنوان IP الراوتر.',
            default                                       => 'خطأ غير متوقع: ' . $message,
        };
    }

    /**
     * التأكد من تحرير الموارد عند تدمير الكلاس
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}
