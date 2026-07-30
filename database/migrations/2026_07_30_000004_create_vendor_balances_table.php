<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('available_balance')->default(0);
            $table->unsignedBigInteger('pending_balance')->default(0);
            $table->unsignedBigInteger('total_earned')->default(0);
            $table->unsignedBigInteger('total_withdrawn')->default(0);
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_balances');
    }
};
