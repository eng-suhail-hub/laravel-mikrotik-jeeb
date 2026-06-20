<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('type', 50)->default('card_purchase')->after('status');
            $table->string('verification_status', 50)->nullable()->after('type');
            $table->decimal('points_amount', 12, 2)->nullable()->after('webhook_amount');
            $table->decimal('points_before', 12, 2)->nullable()->after('points_amount');
            $table->decimal('points_after', 12, 2)->nullable()->after('points_before');
            $table->timestamp('auto_revoke_at')->nullable()->after('card_generated_at');
            $table->timestamp('revoked_at')->nullable()->after('auto_revoke_at');
            $table->boolean('revoke_job_dispatched')->default(false)->after('revoked_at');

            $table->index('verification_status');
            $table->index(['jeeb_reference', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn([
                'type', 'verification_status', 'points_amount',
                'points_before', 'points_after', 'auto_revoke_at',
                'revoked_at', 'revoke_job_dispatched',
            ]);
        });
    }
};
