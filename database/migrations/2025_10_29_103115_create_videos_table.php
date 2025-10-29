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
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')
            ->cascadeOnUpdate()
            ->cascadeOnDelete();
            $table->string('path');
            $table->string('youtube_path')->nullable();
            $table->string('title_en');
            $table->string('title_ar');
            $table->string('subTitle_en')->nullable();
            $table->string('subTitle_ar')->nullable();
            $table->text('description_en');
            $table->text('description_ar');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
