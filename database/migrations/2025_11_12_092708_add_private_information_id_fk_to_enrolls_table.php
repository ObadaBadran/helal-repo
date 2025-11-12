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
        Schema::table('enrolls', function (Blueprint $table) {
            
 $table->unsignedBigInteger('private_information_id')->nullable()->after('course_online_id');

    
    $table->foreign('private_information_id')
          ->references('id')
          ->on('private_lesson_informations')
          ->cascadeOnDelete()
          ->cascadeOnUpdate();
        });
       
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrolls', function (Blueprint $table) {
            //
        });
    }
};
