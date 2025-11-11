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
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('information_id')->constrained('consulation_informations')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('name');
            $table->string('email');
            $table->string('phone');
            $table->enum('payment_status', ['pending', 'paid', 'canceled'])->default('pending');
            $table->string('payment_method')->default('Stripe');
            $table->string('stripe_session_id')->nullable();
            $table->string('meet_url')->nullable();
            $table->boolean('is_done')->default(false);
           // $table->date('consultation_date')->nullable();
            //$table->time('consultation_time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};
