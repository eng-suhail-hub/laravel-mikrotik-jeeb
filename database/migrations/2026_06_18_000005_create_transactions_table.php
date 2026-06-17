<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ════════════════════════════════════════════════════════════════
 *  جدول العمليات (transactions)
 * ════════════════════════════════════════════════════════════════
 *
 *  يمثل كل عملية شراء كرت. الحالات:
 *
 *  - pending_match:  الطلب وصل من Flutter، بانتظار مطابقة Webhook الدفع
 *  - matched:       الـ Webhook وصل وتمت مطابقة المستخدم بنجاح
 *  - processing:    تم وضع توليد الكرت في الـ Queue وجاري التنفيذ
 *  - completed:     تم توليد الكرت في User Manager بنجاح
 *  - failed:        فشلت العملية (خطأ في الراوتر أو بيانات ناقصة)
 *  - manual_pending: الأدمن سيُفعّلها يدوياً (لم تُطابق أي مستخدم)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            // العلاقات
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('profile_id')->constrained('profiles')->restrictOnDelete();
            $table->foreignId('raw_webhook_id')->nullable()->constrained('raw_webhooks')->nullOnDelete();

            // البيانات المستخرجة من الـ Webhook
            $table->string('webhook_phone', 20)->nullable();
            $table->string('webhook_full_name')->nullable();
            $table->decimal('webhook_amount', 12, 2)->nullable();
            $table->string('jeeb_reference', 100)->nullable();

            // بيانات الكرت المولّد (بعد المعالجة)
            // ⚠️ username == password حسب المواصفات الصارمة
            $table->string('mikrotik_username', 32)->nullable();
            $table->string('mikrotik_password', 32)->nullable();
            $table->timestamp('card_generated_at')->nullable();

            // الحالة
            $table->enum('status', [
                'pending_match',
                'matched',
                'processing',
                'completed',
                'failed',
                'manual_pending',
            ])->default('pending_match');

            // سبب الفشل (في حالة failed)
            $table->text('failure_reason')->nullable();

            // من فعّل العملية يدوياً (admin_id)
            $table->foreignId('activated_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();

            $table->timestamps();

            // فهارس لتحسين الاستعلامات في لوحة الأدمن
            $table->index('status');
            $table->index(['webhook_phone', 'webhook_full_name'], 'idx_tx_match');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
