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
        Schema::create('consulation_informations', function (Blueprint $table) {
            $table->id();
            $table->string('type_en')->nullable();
            $table->string('type_ar')->nullable();
            $table->decimal('price_aed', 10, 2)->default(0);
            $table->decimal('price_usd', 10, 2)->default(0);
            $table->integer('duration');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consulation_informations');
    }
};
