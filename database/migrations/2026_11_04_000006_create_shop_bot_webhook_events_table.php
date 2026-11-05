<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_bot_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_bot_id')->constrained()->cascadeOnDelete();
            $table->string('event', 64);
            $table->string('event_key', 191)->nullable(); // idempotence : null autorise les doublons
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->unique(['shop_bot_id', 'event_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_bot_webhook_events');
    }
};
