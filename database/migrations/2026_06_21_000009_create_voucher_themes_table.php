<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voucher_themes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('blade_view', 200);
            $table->string('thumbnail', 255)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_themes');
    }
};
