<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ════════════════════════════════════════════════════════════════
 *  جدول إعدادات الراوتر (router_settings)
 * ════════════════════════════════════════════════════════════════
 *
 *  ⚠️ صف واحد فقط (singleton) — يتغير محتواه عبر لوحة الأدمن.
 *  السبب في جدول وليس في env:
 *  1. الأدمن يُدخلها من الواجهة (لا تحرير يدوي للملفات)
 *  2. نحتاج تخزين حالة الاتصال (is_connected, last_test_at)
 *  3. كلمة مرور الراوتر يجب أن تكون مُشفّرة (encrypted cast)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('router_settings', function (Blueprint $table) {
            $table->id();

            // بيانات الاتصال (IP/Port/Username/Password)
            $table->string('host', 100);                 // مثال: 192.168.88.1
            $table->unsignedSmallInteger('port')->default(8728); // RouterOS API v6
            $table->string('username', 100);             // مثال: admin
            $table->text('password');                    // مُشفّر في الـ Model (encrypted cast)

            // معلومات الراوتر للعرض فقط (تُجلب من الراوتر بعد الاتصال)
            $table->string('router_identity')->nullable();      // مثال: MikroTik-RB1100
            $table->string('routeros_version')->nullable();     // مثال: 6.49.19
            $table->string('board_name')->nullable();           // مثال: RB1100AHx2

            // حالة الاتصال (تُحدّث بعد كل اختبار)
            $table->boolean('is_connected')->default(false);
            $table->timestamp('last_test_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('router_settings');
    }
};
