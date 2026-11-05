<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cache local : la boîte de réception lit ici pour ne pas consommer le
        // budget commun de 60 requêtes par minute.
        Schema::create('shop_bot_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_bot_id')->constrained()->cascadeOnDelete();
            $table->string('channel', 32)->default('whatsapp');
            $table->string('botisimo_conversation_id');
            $table->string('client_name')->nullable();
            $table->string('client_phone', 32)->nullable();
            $table->string('status', 32)->default('active');
            $table->boolean('ai_enabled')->default(true);
            $table->text('last_message_preview')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('last_inbound_at')->nullable(); // ouvre la fenêtre de 24 h
            $table->unsignedInteger('unread_count')->default(0);
            $table->timestamps();

            $table->unique(['shop_bot_id', 'botisimo_conversation_id'], 'shop_bot_conversations_remote_unique');
            $table->index(['shop_bot_id', 'last_message_at']);
        });

        Schema::create('shop_bot_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_bot_conversation_id')->constrained()->cascadeOnDelete();
            $table->string('message_id')->nullable();
            $table->string('meta_message_id')->nullable()->index();
            $table->string('direction', 16); // inbound | outbound
            $table->string('type', 32)->default('text');
            $table->text('body')->nullable();
            $table->string('status', 32)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['shop_bot_conversation_id', 'message_id'], 'shop_bot_messages_remote_unique');
            $table->index(['shop_bot_conversation_id', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_bot_messages');
        Schema::dropIfExists('shop_bot_conversations');
    }
};
