<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('device_uuid', 255)->nullable()->after('device_token');
            $table->boolean('is_banned')->default(false)->after('device_uuid');
            $table->timestamp('banned_at')->nullable()->after('is_banned');
            $table->text('ban_reason')->nullable()->after('banned_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['device_uuid', 'is_banned', 'banned_at', 'ban_reason']);
        });
    }
};
