<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ════════════════════════════════════════════════════════════════
 *  جدول السجل الخام (raw_webhooks) - سجل مالي غير قابل للتعديل
 * ════════════════════════════════════════════════════════════════
 *
 *  ⚠️ هذا الجدول هو المرجع المالي الرسمي للنظام.
 *  كل إشعار دفع يصل من الـ Emulator يُسجّل هنا حرفياً:
 *    - نص الإشعار الكامل (raw_payload)
 *    - الوقت بدقة الملي ثانية
 *    - عنوان IP للمُرسل (يجب أن يكون 127.0.0.1)
 *
 *  لا يُسمح بالتعديل أو الحذف (audit-only) — يُنفّذ ذلك
 *  في الـ Model عبر $guarded = ['*'] أو booted() method.
 *
 *  سبب هذا الفصل:
 *  1. المرجع المالي الرسمي (لا يمكن التلاعب به)
 *  2. في حال فشل الـ Parser، يبقى النص موجوداً للمحاولة اليدوية
 *  3. مطابقة لاحقة (لـ reconciliation) مع تقارير محفظة جيب
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raw_webhooks', function (Blueprint $table) {
            $table->id();

            // النص الخام للإشعار (notification text أو JSON كامل)
            $table->longText('raw_payload');

            // عنوان IP للمُرسل (يجب أن يكون 127.0.0.1)
            $table->string('source_ip', 45);

            // اسم التطبيق المُرسل (مثلاً: com.jeeb.wallet)
            $table->string('source_app', 100)->nullable();

            // الوقت بدقة الملي ثانية (Unix timestamp * 1000)
            $table->unsignedBigInteger('received_at_ms');

            // هل تمت معالجة الإشعار بنجاح (تم استخراج البيانات)
            $table->boolean('parsed_successfully')->default(false);

            // سبب فشل الـ Parser (إن وُجد)
            $table->text('parse_error')->nullable();

            $table->timestamp('created_at')->useCurrent();
        });

        // ⚠️ هذا الجدول لا يستخدم updated_at لأنه غير قابل للتعديل
        // يُمنع UPDATE / DELETE عبر الـ Model (booted method)
    }

    public function down(): void
    {
        Schema::dropIfExists('raw_webhooks');
    }
};
