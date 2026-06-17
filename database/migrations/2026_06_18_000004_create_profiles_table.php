<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ════════════════════════════════════════════════════════════════
 *  جدول الباقات (profiles)
 * ════════════════════════════════════════════════════════════════
 *
 *  كل صف يمثل باقة معروضة للبيع في تطبيق Flutter.
 *  الحقل mikrotik_profile_name يجب أن يطابق اسم الباقة الموجودة
 *  في User Manager داخل الراوتر (يُنشأ يدوياً عبر WinBox من الأدمن).
 *
 *  مثال:
 *  - name: "باقة 5000 ريال - يوم كامل"
 *  - price: 5000.00
 *  - duration_hours: 24
 *  - mikrotik_profile_name: "um-5k-daily"
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);                 // اسم الباقة للعرض في Flutter

            // اسم الباقة داخل User Manager (يجب أن يكون موجوداً فعلياً)
            $table->string('mikrotik_profile_name', 100);

            // السعر (decimal لتجنّب أخطاء الفاصلة العائمة)
            $table->decimal('price', 12, 2);

            // المدة بالساعات (تُستخدم لعرض "صالح لـ X ساعة" في الواجهة)
            $table->unsignedInteger('duration_hours');

            // الحد الأقصى للسرعة (اختياري — يُسجّل فقط للمرجعية)
            $table->string('speed_limit', 50)->nullable();

            // حالة الباقة (نشطة / معطلة)
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
