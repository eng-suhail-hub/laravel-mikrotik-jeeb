<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ════════════════════════════════════════════════════════════════
 *  جدول العملاء (Users) - تطبيق Flutter
 * ════════════════════════════════════════════════════════════════
 *
 *  ⚠️ حسب المواصفات: لا يوجد تسجيل دخول متكرر.
 *  الهدف الوحيد من الحساب: تسجيل الاسم الرباعي + رقم الهاتف
 *  لمطابقتهما لاحقاً مع إشعار الدفع القادم من محفظة جيب.
 *
 *  لذلك لا يوجد:
 *  - password (لا حاجة لمصادقة متكررة)
 *  - email_verified_at
 *  - remember_token
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // الاسم الرباعي (مثال: "محمد أحمد علي الحضرمي")
            // نخزّنه كما هو (بدون تشفير) لأن مطابقة الـ Webhook تعتمد عليه حرفياً
            $table->string('full_name', 255);

            // رقم الهاتف بصيغة موحّدة (يُطبّع في الـ Controller قبل الحفظ)
            // مثال: "9677XXXXXXXX"
            $table->string('phone', 20)->unique();

            // الـ Device Token (FCM) لإرسال إشعار "تم توليد الكرت"
            $table->string('device_token')->nullable();

            $table->timestamps();

            // فهرس مركّب لتسريع المطابقة عند استقبال الـ Webhook
            $table->index(['full_name', 'phone'], 'idx_user_match');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
