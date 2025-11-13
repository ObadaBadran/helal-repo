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
        Schema::create('private_lesson_informations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained('appointments')->onDelete('cascade');
            $table->foreignId('private_lesson_id')->constrained('private_lessons')->onDelete('cascade');
            $table->string('place_ar');
            $table->string('place_en');
            $table->decimal('price_aed', 10, 2)->default(0);
            $table->decimal('price_usd', 10, 2)->default(0);
            // $table->integer('duration');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('private_lesson_informations');
    }
};
