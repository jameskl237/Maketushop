<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_bot_credit_usages', function (Blueprint $table) {
            $table->id();
            // Le journal survit à la boutique : ces crédits ont été payés par la plateforme.
            $table->foreignId('shop_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 32); // message | marketing | broadcast
            $table->unsignedInteger('credits');
            $table->string('reference')->nullable();
            $table->timestamps();

            $table->index(['shop_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_bot_credit_usages');
    }
};
