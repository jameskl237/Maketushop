<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cinetpay_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->unique()->index();
            $table->string('site_id');
            $table->unsignedBigInteger('amount');
            $table->string('currency', 3)->default('XOF');
            $table->string('status'); // CREATED, VALIDATED, REFUSED, CANCELLED, EXPIRED
            $table->string('payment_method')->nullable();
            $table->string('cpm_trans_id')->nullable()->unique();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('description')->nullable();
            $table->text('raw_request')->nullable();
            $table->text('raw_response')->nullable();
            $table->text('raw_webhook')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cinetpay_transactions');
    }
};
