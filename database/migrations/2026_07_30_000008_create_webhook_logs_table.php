<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('cinetpay');
            $table->string('event')->nullable();
            $table->string('transaction_id')->nullable()->index();
            $table->string('status')->default('received');
            $table->text('headers')->nullable();
            $table->text('payload')->nullable();
            $table->text('response')->nullable();
            $table->text('error')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};
