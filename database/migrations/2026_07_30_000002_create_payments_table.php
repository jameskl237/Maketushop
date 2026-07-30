<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->morphs('payable');
            $table->string('provider')->default('cinetpay');
            $table->string('transaction_id')->unique()->index();
            $table->string('reference')->unique()->index();
            $table->unsignedBigInteger('amount');
            $table->string('currency', 3)->default('XOF');
            $table->string('status'); // pending, success, failed, cancelled
            $table->string('payment_method')->nullable(); // ORANGE_MONEY, MTN_MOMO, CARD
            $table->json('provider_data')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
