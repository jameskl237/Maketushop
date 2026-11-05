<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_bots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('channel', 32)->default('whatsapp');
            $table->string('botisimo_bot_id')->nullable()->unique();
            $table->string('status', 32)->default('pending');
            $table->text('last_error')->nullable();

            // Configuration de l'IA
            $table->string('ai_model')->nullable();
            $table->text('ai_instructions')->nullable();
            $table->text('additional_instructions')->nullable();
            $table->decimal('ai_temperature', 3, 2)->nullable();
            $table->string('tone')->nullable();
            $table->string('language', 8)->default('fr');
            $table->text('delivery_policy')->nullable();
            $table->text('return_policy')->nullable();
            $table->boolean('welcome_message_enabled')->default(true);
            $table->text('welcome_message')->nullable();
            $table->boolean('away_message_enabled')->default(false);
            $table->text('away_message')->nullable();
            $table->json('human_transfer_keywords')->nullable();
            $table->string('business_address')->nullable();
            $table->boolean('ai_share_location')->default(false);

            // Connexion WhatsApp
            $table->string('whatsapp_phone_number')->nullable();
            $table->string('phone_number_id')->nullable();
            $table->string('quality_rating')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamp('token_expiry_notified_at')->nullable();
            $table->text('webhook_secret')->nullable();

            $table->boolean('catalog_ai_enabled')->default(false);
            $table->unsignedInteger('monthly_credit_quota')->nullable(); // null = valeur de config/botisimo.php
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['shop_id', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_bots');
    }
};
