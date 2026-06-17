<?php

namespace App\Jobs;

use App\Models\Transaction;
use App\Services\CardGeneratorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ════════════════════════════════════════════════════════════════
 *  وظيفة توليد الكرت في الراوتر (Queue Job)
 * ════════════════════════════════════════════════════════════════
 *
 *  ⚠️ هذه الوظيفة هي المسار الوحيد لإنشاء كروت فعلياً.
 *  تُنفّذ من خلال:
 *    php artisan queue:work --queue=cards
 *
 *  ضمانات:
 *  - تتابعية (لا overload على الراوتر PowerPC)
 *  - إعادة محاولة تلقائية عند الفشل المؤقت
 *  - تحرير القفل عند الانتهاء (عبر CardGeneratorService)
 *
 *  في حالة انقطاع الراوتر:
 *  - الـ Job يفشل مع "فشل الاتصال"
 *  - يُعاد تلقائياً 3 مرات مع backoff تصاعدي
 *  - بعدها يبقى في failed_jobs للمراجعة اليدوية
 */
class GenerateMikrotikCardJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * اسم الطابور (يجب أن يتطابق مع إعدادات الـ worker)
     */
    public string $queue = 'cards';

    /**
     * عدد المحاولات قبل اعتبار العملية فاشلة نهائياً
     */
    public int $tries = 3;

    /**
     * مهلة تنفيذ الوظيفة بالثواني
     */
    public int $timeout = 60;

    /**
     * @param int $transactionId معرّف العملية في جدول transactions
     */
    public function __construct(public int $transactionId)
    {
    }

    /**
     * حساب وقت الانتظار قبل إعادة المحاولة (بالثواني)
     * - المحاولة 1: 10 ثواني
     * - المحاولة 2: 30 ثانية
     * - المحاولة 3: 90 ثانية
     */
    public function backoff(): array
    {
        return [10, 30, 90];
    }

    /**
     * تنفيذ الوظيفة
     */
    public function handle(CardGeneratorService $generator): void
    {
        $transaction = Transaction::find($this->transactionId);

        if (!$transaction) {
            Log::error('GenerateMikrotikCardJob: Transaction not found', [
                'id' => $this->transactionId,
            ]);
            return;
        }

        // إذا كانت العملية مكتملة مسبقاً، لا تكرر
        if ($transaction->status === Transaction::STATUS_COMPLETED) {
            Log::info('GenerateMikrotikCardJob: Already completed', [
                'id' => $this->transactionId,
            ]);
            return;
        }

        // تحديث الحالة إلى processing قبل البدء
        $transaction->update(['status' => Transaction::STATUS_PROCESSING]);

        Log::info('GenerateMikrotikCardJob: Starting', [
            'transaction_id' => $this->transactionId,
            'attempt' => $this->attempts(),
        ]);

        $generator->generate($transaction);
    }

    /**
     * عند فشل المحاولة الأخيرة
     */
    public function failed(\Throwable $exception): void
    {
        $transaction = Transaction::find($this->transactionId);

        if ($transaction) {
            $transaction->update([
                'status' => Transaction::STATUS_FAILED,
                'failure_reason' => 'فشل نهائي بعد ' . $this->tries . ' محاولات: ' . $exception->getMessage(),
            ]);
        }

        Log::error('GenerateMikrotikCardJob: Final failure', [
            'transaction_id' => $this->transactionId,
            'error' => $exception->getMessage(),
        ]);
    }
}
