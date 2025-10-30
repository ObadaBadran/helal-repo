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
        Schema::create('enrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')
            ->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('user_id')->constrained('users')
            ->cascadeOnDelete()->cascadeOnUpdate();
            $table->enum('payment_status', ['pending', 'paid', 'cancelled'])->default('pending');
            $table->string('payment_method')->nullable(); // card, paypal, wallet...
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('transaction_id')->nullable();
            $table->enum('currency', ['USD', 'AED'])->default('USD');
            $table->boolean('is_enroll')->default(0);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrolls');
    }
};
