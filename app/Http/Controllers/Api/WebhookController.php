<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateMikrotikCardJob;
use App\Models\Profile;
use App\Models\RawWebhook;
use App\Models\Transaction;
use App\Models\User;
use App\Services\WebhookParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * ════════════════════════════════════════════════════════════════
 *  متحكم Webhook إشعارات محفظة جيب (من Android Emulator)
 * ════════════════════════════════════════════════════════════════
 *
 *  ⚠️ محمي عبر LocalhostOnly middleware (127.0.0.1 + API Key)
 *
 *  التدفق:
 *  1. Emulator يبث إشعار جيب
 *  2. الـ Webhook يصل هنا
 *  3. يُسجّل النص الخام في raw_webhooks (سجل مالي غير قابل للتعديل)
 *  4. WebhookParser يستخرج البيانات
 *  5. البحث عن العميل المطابق (full_name + phone)
 *  6. ⚠️ إذا وُجدت عملية pending_match بنفس المبلغ → تُربط وتُجدول
 *  7. ⚠️ إذا لم يوجد عميل → تُنشأ transaction في manual_pending (للأدمن)
 */
class WebhookController extends Controller
{
    public function __construct(private WebhookParser $parser) {}

    /**
     * استقبال إشعار الدفع من Emulator
     */
    public function receive(Request $request): JsonResponse
    {
        // 1. استخراج النص الخام (يدعم عدة صيغ)
        $rawText = $this->extractRawText($request);

        // 2. ⚠️ حفظ السجل المالي فوراً (قبل أي تحليل)
        // هذا يضمن عدم فقدان الإثبات حتى لو فشل التحليل
        $rawWebhook = RawWebhook::create([
            'raw_payload' => $rawText,
            'source_ip' => $request->ip(),
            'source_app' => config('jeeb.emulator_package'),
            'received_at_ms' => (int) round(microtime(true) * 1000),
        ]);

        // 3. تحليل النص
        $parsed = $this->parser->parse($rawText);

        if (! $parsed['success']) {
            $rawWebhook->update([
                'parsed_successfully' => false,
                'parse_error' => $parsed['error'],
            ]);

            Log::warning('Webhook parse failed', [
                'raw_webhook_id' => $rawWebhook->id,
                'error' => $parsed['error'],
                'payload_preview' => mb_substr($rawText, 0, 200),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل تحليل الإشعار. تم حفظ النص للمراجعة.',
                'raw_id' => $rawWebhook->id,
            ], 422);
        }

        $rawWebhook->update(['parsed_successfully' => true]);

        // 4. التحقق من وجود عملية instant-delivery بنفس الـ reference
        if ($parsed['reference']) {
            $pendingTx = Transaction::where('jeeb_reference', $parsed['reference'])
                ->where('verification_status', Transaction::VERIFICATION_PENDING)
                ->first();

            if ($pendingTx) {
                $pendingTx->update([
                    'verification_status' => Transaction::VERIFICATION_VERIFIED,
                    'raw_webhook_id' => $rawWebhook->id,
                ]);

                Log::info('Webhook verified instant-delivery transaction', [
                    'transaction_id' => $pendingTx->id,
                    'reference' => $parsed['reference'],
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'تم تأكيد الدفع واستقرار الكرت.',
                    'verified' => true,
                    'transaction_id' => $pendingTx->id,
                ]);
            }
        }

        // 5. البحث عن العميل المطابق
        $user = User::findByMatch($parsed['full_name'], $parsed['phone']);

        if (! $user) {
            // ⚠️ لم يُطابق — يُنشأ كعملية يدوية معلقة
            $this->createManualPendingTransaction($parsed, $rawWebhook);

            return response()->json([
                'success' => true,
                'message' => 'الإشعار غير مطابق لأي عميل مسجل. سيُفعّله الأدمن يدوياً.',
                'matched' => false,
                'raw_id' => $rawWebhook->id,
            ]);
        }

        // 5. البحث عن عملية pending_match لهذا العميل بنفس المبلغ
        $amount = $parsed['amount'];
        $transaction = Transaction::where('user_id', $user->id)
            ->where('status', Transaction::STATUS_PENDING_MATCH)
            ->where('webhook_amount', $amount)
            ->latest()
            ->first();

        // إذا لم توجد عملية معلقة، نبحث عن أحدث عملية pending بدون ربط
        if (! $transaction) {
            $transaction = Transaction::where('user_id', $user->id)
                ->where('status', Transaction::STATUS_PENDING_MATCH)
                ->latest()
                ->first();
        }

        if (! $transaction) {
            // ⚠️ العميل مسجّل لكن لا يوجد طلب شراء منه
            // → يُنشأ transaction مرتبطة بالعميل، يختار الأدمن الباقة
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'profile_id' => $this->guessProfileByAmount($amount)?->id,
                'raw_webhook_id' => $rawWebhook->id,
                'webhook_phone' => $parsed['phone'],
                'webhook_full_name' => $parsed['full_name'],
                'webhook_amount' => $amount,
                'jeeb_reference' => $parsed['reference'],
                'status' => Transaction::STATUS_MANUAL_PENDING,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'العميل مسجّل لكن لم يُرسل طلب شراء. بانتظار الأدمن.',
                'matched' => true,
                'transaction_id' => $transaction->id,
            ]);
        }

        // 6. ✅ تمت المطابقة → ربط البيانات + جدولة التوليد
        $transaction->update([
            'raw_webhook_id' => $rawWebhook->id,
            'webhook_phone' => $parsed['phone'],
            'webhook_full_name' => $parsed['full_name'],
            'webhook_amount' => $amount,
            'jeeb_reference' => $parsed['reference'],
            'status' => Transaction::STATUS_MATCHED,
        ]);

        // ⚠️ جدولة التوليد في Queue (لا تنفيذ مباشر)
        GenerateMikrotikCardJob::dispatch($transaction->id);

        return response()->json([
            'success' => true,
            'message' => 'تمت المطابقة بنجاح. سيتم توليد الكرت.',
            'matched' => true,
            'transaction_id' => $transaction->id,
        ]);
    }

    /**
     * استخراج النص الخام من الطلب (يدعم JSON, form-data, raw)
     */
    private function extractRawText(Request $request): string
    {
        // محاولة 1: حقل "text" أو "message" أو "body" في الـ body
        if ($text = $request->input('text')) {
            return (string) $text;
        }
        if ($text = $request->input('message')) {
            return (string) $text;
        }
        if ($text = $request->input('body')) {
            return (string) $text;
        }

        // محاولة 2: JSON كامل كـ string
        if ($request->isJson()) {
            return $request->getContent();
        }

        // محاولة 3: النص الخام
        return $request->getContent();
    }

    /**
     * إنشاء عملية معلقة يدوياً (العميل غير مسجّل)
     */
    private function createManualPendingTransaction(array $parsed, RawWebhook $rawWebhook): Transaction
    {
        return Transaction::create([
            'user_id' => null,
            'profile_id' => $this->guessProfileByAmount($parsed['amount'])?->id
                ?? Profile::active()->first()?->id
                ?? 1, // قيمة افتراضية (الأدمن يصححها)
            'raw_webhook_id' => $rawWebhook->id,
            'webhook_phone' => $parsed['phone'],
            'webhook_full_name' => $parsed['full_name'],
            'webhook_amount' => $parsed['amount'],
            'jeeb_reference' => $parsed['reference'],
            'status' => Transaction::STATUS_MANUAL_PENDING,
        ]);
    }

    /**
     * تخمين الباقة المناسبة بناءً على المبلغ
     * (أفضل تخمين — الأدمن يُعدّلها إذا لزم)
     */
    private function guessProfileByAmount(?float $amount): ?Profile
    {
        if (! $amount) {
            return null;
        }

        return Profile::active()
            ->where('price', '<=', $amount)
            ->orderBy('price', 'desc')
            ->first();
    }
}
