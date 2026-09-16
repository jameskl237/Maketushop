<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rétablit notify_token : l'API CinetPay v1 authentifie ses notifications avec
 * ce jeton renvoyé à l'initialisation. La colonne avait été supprimée en la
 * croyant propre à une API Checkout v2 dont l'hôte n'existe pas.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('cinetpay_transactions', 'notify_token')) {
            return;
        }

        Schema::table('cinetpay_transactions', function (Blueprint $table) {
            $table->string('notify_token')->nullable()->after('cpm_trans_id');
        });
    }

    public function down(): void
    {
        Schema::table('cinetpay_transactions', function (Blueprint $table) {
            $table->dropColumn('notify_token');
        });
    }
};
