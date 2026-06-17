<?php

namespace App\Services;

use App\Models\Profile;
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
     * @param Transaction $transaction العملية المطلوب تنفيذها
     * @return Transaction العملية بعد التحديث
     * @throws \RuntimeException إذا فشلت العملية
     */
    public function generate(Transaction $transaction): Transaction
    {
        // تحميل العلاقات المطلوبة
        $transaction->load(['profile', 'user']);

        if (!$transaction->profile) {
            throw new \RuntimeException('الباقة غير موجودة في جدول profiles.');
        }

        // ⚠️ مواصفات صارمة: username == password
        // توليد سلسلة عشوائية (أحرف كبيرة + أرقام، 10 خانات)
        $credentials = $this->generateCredentials(10);

        $mikrotik = app(MikroTikService::class);

        try {
            $mikrotik->connect();

            $mikrotik->createUserManagerUser(
                $credentials,
                $credentials,
                $transaction->profile->mikrotik_profile_name
            );

            // تحديث العملية بنجاح
            $transaction->update([
                'mikrotik_username' => $credentials,
                'mikrotik_password' => $credentials,
                'card_generated_at' => now(),
                'status' => Transaction::STATUS_COMPLETED,
                'failure_reason' => null,
            ]);

            Log::info('Card generated successfully', [
                'transaction_id' => $transaction->id,
                'username' => $credentials,
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
     * توليد اسم مستخدم وكلمة مرور عشوائيين (متطابقين)
     *
     * ⚠️ حسب المواصفات:
     * - أحرف وأرقام فقط (لا رموز خاصة)
     * - طول قابل للضبط (افتراضي 10 خانات)
     * - username == password
     *
     * @param int $length طول السلسلة (6-32)
     * @return string
     */
    public function generateCredentials(int $length = 10): string
    {
        $length = max(6, min(32, $length));

        // أحرف كبيرة + أرقام (لتجنب مشاكل الـ encoding في المايكروتك)
        // استبعاد 0/O و 1/I/L لتجنب اللبس البصري
        $charset = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

        $bytes = random_bytes($length);
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $result .= $charset[ord($bytes[$i]) % strlen($charset)];
        }

        return $result;
    }
}
