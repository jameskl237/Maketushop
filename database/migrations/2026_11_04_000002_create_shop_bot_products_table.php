<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_bot_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_bot_id')->constrained()->cascadeOnDelete();
            // Produit ou service. Sans clé étrangère : la ligne doit survivre à la
            // suppression locale pour pouvoir envoyer le DELETE à Botisimo.
            $table->string('catalogable_type', 32);
            $table->unsignedBigInteger('catalogable_id');
            $table->string('botisimo_product_id')->nullable();
            $table->boolean('is_ad')->default(false);
            $table->string('sync_hash', 64)->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->text('sync_error')->nullable();
            $table->timestamps();

            $table->unique(['shop_bot_id', 'catalogable_type', 'catalogable_id'], 'shop_bot_products_catalogable_unique');
            $table->index(['catalogable_type', 'catalogable_id'], 'shop_bot_products_catalogable_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_bot_products');
    }
};
