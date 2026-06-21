<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

/**
 * ════════════════════════════════════════════════════════════════
 *  كلاس توليد الكروت (Card Generator)
 * ════════════════════════════════════════════════════════════════
 *
 *  مسؤولياته:
 *  1. توليد اسم مستخدم وكلمة مرور عشوائيين (متطابقين)
 *  2. استخدام MikroTikService لإضافتهما في User Manager
 *  3. تحديث حالة Transaction
 *
 *  ⚠️ هذا الكلاس لا يتعامل مع:
 *  - تحليل الـ Webhook (WebookParser)
 *  - الـ HTTP (Controllers)
 *  - الـ Queue dispatching (Jobs)
 *
 *  فقط منطق توليد الكرت.
 */
class CardGeneratorService
{
    /**
     * توليد كرت لعملية محددة
     *
     * @param  Transaction  $transaction  العملية المطلوب تنفيذها
     * @param  array  $config  إعدادات التوليد الاختيارية (credential_mode, username_length, etc.)
     * @return Transaction العملية بعد التحديث
     *
     * @throws \RuntimeException إذا فشلت العملية
     */
    public function generate(Transaction $transaction, array $config = []): Transaction
    {
        // تحميل العلاقات المطلوبة
        $transaction->load(['profile', 'user']);

        if (! $transaction->profile) {
            throw new \RuntimeException('الباقة غير موجودة في جدول profiles.');
        }

        // توليد بيانات الدخول باستخدام الإعدادات الممررة
        $credentials = $this->generateCredentials($config);

        $mikrotik = app(MikroTikService::class);

        try {
            $mikrotik->connect();

            $mikrotik->createUserManagerUser(
                $credentials['username'],
                $credentials['password'],
                $transaction->profile->mikrotik_profile_name
            );

            // تحديث العملية بنجاح
            $transaction->update([
                'mikrotik_username' => $credentials['username'],
                'mikrotik_password' => $credentials['password'],
                'card_generated_at' => now(),
                'status' => Transaction::STATUS_COMPLETED,
                'failure_reason' => null,
            ]);

            Log::info('Card generated successfully', [
                'transaction_id' => $transaction->id,
                'username' => $credentials['username'],
                'profile' => $transaction->profile->mikrotik_profile_name,
            ]);

            return $transaction->fresh();

        } catch (\Throwable $e) {
            // تحديث العملية بفشل
            $transaction->update([
                'status' => Transaction::STATUS_FAILED,
                'failure_reason' => $e->getMessage(),
            ]);

            Log::error('Card generation failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * توليد اسم مستخدم وكلمة مرور عشوائيين
     *
     * حسب الإعدادات:
     * - credential_mode: 'match' (username == password) أو 'separate'
     * - username_length, password_length: طول الخانات
     * - username_prefix: بادئة اسم المستخدم
     * - charset: المحارف المسموحة
     *
     * @param  array  $config  إعدادات التوليد
     * @return array ['username' => string, 'password' => string]
     */
    public function generateCredentials(array $config = []): array
    {
        $mode = $config['credential_mode'] ?? 'match';
        $usernameLength = max(6, min(32, $config['username_length'] ?? 10));
        $passwordLength = max(6, min(32, $config['password_length'] ?? 10));
        $prefix = $config['username_prefix'] ?? '';
        $charset = $config['charset'] ?? 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $exclude = $config['exclude_chars'] ?? '0O1IL';

        $charset = preg_replace('/['.preg_quote($exclude, '/').']/', '', $charset);
        if (empty($charset)) {
            $charset = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        }

        $username = $this->randomString($usernameLength, $charset);
        $password = $mode === 'match'
            ? $username
            : $this->randomString($passwordLength, $charset);

        return [
            'username' => $prefix.$username,
            'password' => $password,
        ];
    }

    /**
     * توليد سلسلة عشوائية من مجموعة محارف محددة
     */
    private function randomString(int $length, string $charset): string
    {
        $bytes = random_bytes($length);
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= $charset[ord($bytes[$i]) % strlen($charset)];
        }

        return $result;
    }
}
