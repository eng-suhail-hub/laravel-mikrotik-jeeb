<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('challenges', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('max_completions')->default(0);
            $table->timestamps();
        });

        Schema::create('challenge_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('challenge_id')->constrained('challenges')->cascadeOnDelete();
            $table->string('condition_type', 100);
            $table->string('operator', 20)->default('gte');
            $table->json('value');
            $table->unsignedTinyInteger('logic_group')->default(0);
            $table->timestamps();
        });

        Schema::create('challenge_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('challenge_id')->constrained('challenges')->cascadeOnDelete();
            $table->string('reward_type', 100);
            $table->json('value');
            $table->unsignedTinyInteger('priority')->default(0);
            $table->timestamps();
        });

        Schema::create('user_challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('challenge_id')->constrained('challenges')->cascadeOnDelete();
            $table->json('progress_data')->nullable();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('reward_claimed_at')->nullable();
            $table->unsignedInteger('completion_count')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'challenge_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_challenges');
        Schema::dropIfExists('challenge_rewards');
        Schema::dropIfExists('challenge_conditions');
        Schema::dropIfExists('challenges');
    }
};
