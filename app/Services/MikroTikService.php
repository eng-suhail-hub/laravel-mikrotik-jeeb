<?php

namespace App\Services;

use App\Models\RouterSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RouterOS\Client;
use RouterOS\Exceptions\ConnectException;
use RouterOS\Exceptions\QueryException;
use RouterOS\Query; // ⚠️ إضافة ضرورية جداً لكتابة أوامر الإضافة والتعديل
use Throwable;

/**
 * ════════════════════════════════════════════════════════════════
 * كلاس خدمة المايكروتك (MikroTik Service)
 * ════════════════════════════════════════════════════════════════
 *
 * ⚠️ هذا الكلاس هو الجسر الوحيد بين Laravel والراوتر.
 * مسؤولياته:
 * 1. فتح/إغلاق اتصال مع RouterOS v6 عبر منفذ 8728
 * 2. استخدام Laravel Cache::lock لمنع أكثر من اتصال متزامن
 * 3. تنفيذ الأوامر على User Manager فقط (ليس Hotspot)
 * 4. رمي استثناءات واضحة يمكن للـ Job التعامل معها
 */
class MikroTikService
{
    private ?Client $client = null;

    private bool $lockAcquired = false;

    private mixed $lock = null;

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

        if (! $setting->isConnectable()) {
            throw new \RuntimeException(
                'بيانات الراوتر غير مكتملة. يرجى إدخال IP/Username/Password من لوحة الأدمن.'
            );
        }

        $lockKey = config('mikrotik.queue_lock_key', 'mikrotik_connection_lock');
        $lockTtl = config('mikrotik.queue_lock_ttl', 30);

        $this->lock = Cache::lock($lockKey, $lockTtl);

        $this->lockAcquired = $this->lock->get();

        if (! $this->lockAcquired) {
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
                'legacy' => false, // v6.49 يستخدم المصادقة الحديثة (post-6.43)
            ]);

            $this->client->query('/system/identity/print')->read();

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
        $this->client = null;
        $this->releaseLock();
    }

    /**
     * تحرير الـ Lock بشكل آمن
     */
    private function releaseLock(): void
    {
        if ($this->lockAcquired && $this->lock !== null) {
            try {
                $this->lock->release();
            } catch (Throwable $e) {
                // تجاهل أخطاء التحرير
            }
            $this->lockAcquired = false;
            $this->lock = null;
        }
    }

    /**
     * إنشاء مستخدم وربطه بالباقة في User Manager
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
            // ⚠️ التصحيح 1: استخدام RouterOS\Query لعمليات الـ Add لإرسال المعاملات بعلامة (=)
            $addPath = config('mikrotik.user_manager_add_path', '/tool/user-manager/user/add');

            $addQuery = (new Query($addPath))
                ->equal('customer', 'admin')
                ->equal('name', $username)
                ->equal('password', $password);

            $this->client->query($addQuery)->read();

            // ⚠️ التصحيح 2: في User Manager v6، المعامل الصحيح هو "numbers" وليس "user" لتحديد الحساب
            $profileQuery = (new Query('/tool/user-manager/user/create-and-activate-profile'))
                ->equal('customer', 'admin')
                ->equal('numbers', $username)
                ->equal('profile', $mikrotikProfileName);

            $this->client->query($profileQuery)->read();

            Log::info('User Manager user created', [
                'username' => $username,
                'profile' => $mikrotikProfileName,
            ]);

            return true;

        } catch (QueryException $e) {
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
     * اختبار الاتصال
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

    private function translateError(string $message): string
    {
        return match (true) {
            str_contains($message, 'Connection refused') => 'الراوتر يرفض الاتصال. تحقق من الـ IP والمنفذ.',
            str_contains($message, 'timeout') => 'انتهت مهلة الاتصال. الراوتر لا يستجيب.',
            str_contains($message, 'Authentication') => 'بيانات الدخول (اسم المستخدم/كلمة المرور) غير صحيحة.',
            str_contains($message, 'Could not resolve') => 'تعذّر الوصول لعنوان IP الراوتر.',
            default => 'خطأ غير متوقع: '.$message,
        };
    }

    /**
     * جلب الباقات من User Manager
     */
    public function getUserManagerProfiles(): array
    {
        if ($this->client === null) {
            $this->connect();
        }

        try {
            // دوال الـ Print لا تحتاج لمعاملات Query، النص المباشر يعمل بشكل ممتاز
            $profiles = $this->client->query('/tool/user-manager/profile/print')->read();
            $profileLimitations = $this->client->query('/tool/user-manager/profile/profile-limitation/print')->read();
            $limitations = $this->client->query('/tool/user-manager/limitation/print')->read();

            $limitationsByName = [];
            foreach ($limitations as $limitation) {
                if (isset($limitation['name'])) {
                    $limitationsByName[$limitation['name']] = $limitation;
                }
            }

            $profileLimitationMap = [];
            foreach ($profileLimitations as $pl) {
                if (isset($pl['profile']) && isset($pl['limitation'])) {
                    $profileLimitationMap[$pl['profile']][] = $pl['limitation'];
                }
            }

            $result = [];
            foreach ($profiles as $profile) {
                if (! isset($profile['name'])) {
                    continue;
                }

                $name = $profile['name'];
                $price = isset($profile['price']) ? (float) $profile['price'] : 0.0;
                $validity = $profile['validity'] ?? '1d';

                $speedLimits = [];
                $uptimeLimit = '';

                if (isset($profileLimitationMap[$name]) && count($profileLimitationMap[$name]) > 0) {
                    foreach ($profileLimitationMap[$name] as $limitationName) {
                        if (isset($limitationsByName[$limitationName])) {
                            $limInfo = $limitationsByName[$limitationName];

                            if (empty($uptimeLimit) && ! empty($limInfo['uptime-limit'])) {
                                $uptimeLimit = $limInfo['uptime-limit'];
                            }

                            $rx = $limInfo['rate-limit-rx'] ?? '';
                            $tx = $limInfo['rate-limit-tx'] ?? '';

                            $rxFormatted = $this->formatBytesToMbps($rx);
                            $txFormatted = $this->formatBytesToMbps($tx);

                            $limitStr = '';
                            if ($rxFormatted && $txFormatted) {
                                $limitStr = "{$rxFormatted}/{$txFormatted}";
                            } elseif ($rxFormatted) {
                                $limitStr = $rxFormatted;
                            }

                            if ($limitStr && ! in_array($limitStr, $speedLimits)) {
                                $speedLimits[] = $limitStr;
                            }
                        }
                    }
                }

                if (empty($validity)) {
                    $validity = $uptimeLimit;
                }

                $speedLimitStr = ! empty($speedLimits) ? implode(' | ', $speedLimits) : null;
                if ($speedLimitStr && mb_strlen($speedLimitStr) > 50) {
                    $speedLimitStr = mb_substr($speedLimitStr, 0, 47).'...';
                }

                $result[] = [
                    'name' => $name,
                    'price' => $price,
                    'validity' => $validity,
                    'speed_limit' => $speedLimitStr,
                ];
            }

            return $result;

        } catch (QueryException $e) {
            Log::error('MikroTik failed to fetch User Manager profiles', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * ⚠️ التصحيح 3: دعم الأرقام الصافية (بدون حرف B) التي يُرجعها الـ API
     */
    private function formatBytesToMbps(string $value): string
    {
        if (empty($value)) {
            return '';
        }
        $value = strtoupper(trim($value));

        // إزالة الحرف B إن وُجد
        if (str_ends_with($value, 'B')) {
            $value = substr($value, 0, -1);
        }

        // تحويل الرقم إلى M أو K بناءً على الحجم
        if (is_numeric($value)) {
            $bytes = (float) $value;
            if ($bytes >= 1048576) {
                return round($bytes / 1048576).'M';
            } elseif ($bytes >= 1024) {
                return round($bytes / 1024).'K';
            }

            return $bytes.'B';
        }

        return $value; // إعادة القيمة كما هي إذا كانت نصية
    }

    public function removeUser(string $username): bool
    {
        if ($this->client === null) {
            $this->connect();
        }

        $query = (new Query('/tool/user-manager/user/remove'))
            ->equal('numbers', $username);

        $this->client->query($query)->read();

        return true;
    }

    public function executeMaintenance(string $action): array
    {
        if ($this->client === null) {
            $this->connect();
        }

        $path = match ($action) {
            'backup_db' => '/tool/user-manager/database/backup',
            'clear_logs' => '/tool/user-manager/database/clear-logs',
            'rebuild_db' => '/tool/user-manager/database/rebuild',
            default => throw new \InvalidArgumentException("Unknown action: {$action}"),
        };

        $response = $this->client->query($path)->read();

        return ['output' => json_encode($response)];
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}
