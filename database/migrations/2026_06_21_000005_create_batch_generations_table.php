<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batch_generations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->unsignedBigInteger('profile_id');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('generated_count')->default(0);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'partially_completed'])->default('pending');
            $table->json('generation_config')->nullable();
            $table->timestamps();

            $table->index('admin_id');
            $table->index('profile_id');
            $table->foreign('admin_id')->references('id')->on('admins')->nullOnDelete();
            $table->foreign('profile_id')->references('id')->on('profiles')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_generations');
    }
};
