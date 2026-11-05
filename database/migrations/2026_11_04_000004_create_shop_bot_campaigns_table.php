<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_bot_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_bot_id')->constrained()->cascadeOnDelete();
            $table->string('channel', 32)->default('whatsapp');
            $table->string('botisimo_template_id')->nullable();
            $table->string('name');
            $table->string('category', 32);
            $table->string('language', 16);
            $table->string('status', 32)->default('PENDING');
            $table->json('components')->nullable();
            $table->timestamps();

            $table->unique(['shop_bot_id', 'name', 'language']);
        });

        Schema::create('shop_bot_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_bot_id')->constrained()->cascadeOnDelete();
            $table->string('channel', 32)->default('whatsapp');
            $table->string('template_name');
            $table->unsignedInteger('recipients_count')->default(0);
            $table->unsignedInteger('sent')->default(0);
            $table->unsignedInteger('failed')->default(0);
            $table->unsignedInteger('credits_used')->default(0);
            $table->string('status', 32)->default('draft');
            $table->json('payload')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_bot_campaigns');
        Schema::dropIfExists('shop_bot_templates');
    }
};
