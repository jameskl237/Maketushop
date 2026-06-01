<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * NOTE: La table `ratings` est déjà créée par la migration
     * 2026_05_13_000001_create_ratings_table. Cette migration (issue d'un merge)
     * faisait doublon ; on la rend idempotente pour ne pas casser une base fraîche.
     */
    public function up(): void
    {
        if (Schema::hasTable('ratings')) {
            return;
        }

        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->morphs('rateable');
            $table->unsignedTinyInteger('score')->default(5);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index(['rateable_type', 'rateable_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * On ne supprime pas la table ici : elle appartient à la migration
     * 2026_05_13_000001_create_ratings_table.
     */
    public function down(): void
    {
        // no-op
    }
};
