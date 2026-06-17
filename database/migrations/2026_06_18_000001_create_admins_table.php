<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ════════════════════════════════════════════════════════════════
 *  جدول الأدمن (لوحة التحكم)
 * ════════════════════════════════════════════════════════════════
 *
 *  منفصل تماماً عن جدول users (العملاء) لمنع أي خلط في الصلاحيات.
 *  يستخدم Laravel Sanctum أو Session Guard العادي.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('password'); // Hash
            $table->string('full_name')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
