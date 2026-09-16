<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cycle de vie d'une commande après paiement :
 *   pending ──paiement validé──> in_progress ──confirmation client──> delivered
 *                                     └──annulation (72 h puis 7 j)──> cancelled
 *
 * Le vendeur dépose une photo de livraison : c'est la preuve exigée pour être
 * payé, mais c'est la confirmation du client qui libère l'escrow.
 */
return new class extends Migration
{
    public function up(): void
    {
        // enum() ->change() ne sait pas étendre proprement une énumération MySQL.
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','in_progress','delivered','cancelled') NOT NULL DEFAULT 'pending'");

        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('paid_at')->nullable()->after('is_paid');

            $table->string('delivery_proof_path')->nullable()->after('escrow_released_at');
            $table->timestamp('delivery_proof_at')->nullable()->after('delivery_proof_path');
            $table->timestamp('client_confirmed_at')->nullable()->after('delivery_proof_at');

            $table->timestamp('cancellation_offered_at')->nullable()->after('client_confirmed_at');
            $table->timestamp('cancellation_declined_at')->nullable()->after('cancellation_offered_at');
            $table->timestamp('cancelled_at')->nullable()->after('cancellation_declined_at');
            $table->string('cancellation_reason')->nullable()->after('cancelled_at');

            // Les relances planifiées se font par balayage sur ces dates.
            $table->index(['status', 'paid_at']);
        });

        // Les commandes déjà payées basculent dans le nouveau statut courant.
        DB::table('orders')->where('is_paid', true)->where('status', 'pending')->update(['status' => 'in_progress']);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status', 'paid_at']);
            $table->dropColumn([
                'paid_at', 'delivery_proof_path', 'delivery_proof_at', 'client_confirmed_at',
                'cancellation_offered_at', 'cancellation_declined_at', 'cancelled_at', 'cancellation_reason',
            ]);
        });

        DB::table('orders')->whereNotIn('status', ['pending', 'delivered'])->update(['status' => 'pending']);
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','delivered') NOT NULL DEFAULT 'pending'");
    }
};
