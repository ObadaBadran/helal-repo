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
        Schema::create('course_online', function (Blueprint $table) {
            $table->id();
             $table->string('name_en');
            $table->text('description_en');
            $table->string('name_ar');
            $table->text('description_ar');
            // $table->integer('duration');
            $table->decimal('price_aed', 10, 2)->default(0);
            $table->decimal('price_usd', 10, 2)->default(0);
            // $table->dateTime('date');
            $table->string('cover_image')->nullable();
            $table->string('meet_url')->nullable();
            $table->boolean('active')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_online');
    }
};
