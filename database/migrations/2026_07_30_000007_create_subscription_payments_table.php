<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_subscription_id')->constrained()->cascadeOnDelete();
            $table->string('plan');
            $table->unsignedBigInteger('amount');
            $table->string('transaction_id')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('status')->default('pending'); // pending, success, failed
            $table->json('provider_data')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('period_start')->nullable();
            $table->timestamp('period_end')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};
