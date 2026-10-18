<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendancelogs', function (Blueprint $table) {
            $table->id();
            $table->string('user_code')->default('USR-001');
            $table->string('user_name')->default('Muhammad Asla Labiib Badaly');
            $table->integer('eyes_detected')->default(2);
            $table->boolean('is_fatigue')->default(false);
            $table->enum('recommendation', ['FIT_FOR_WORK', 'WARNING_FATIGUE'])->default('FIT_FOR_WORK');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendancelogs');
    }
};
