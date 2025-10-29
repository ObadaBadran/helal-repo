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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title_en');
            $table->string('title_ar');
            $table->string('subTitle_en')->nullable();
            $table->string('subTitle_ar')->nullable();
            $table->text('description_en');
            $table->text('description_ar');
            $table->decimal('price_aed', 10, 2);
            $table->decimal('price_usd', 10, 2);
            $table->integer('reviews');
            $table->string('image')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
