<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * notify_token était propre à l'API v1 (OAuth) de CinetPay, abandonnée au profit
 * de l'API Checkout v2 : les notifications y sont authentifiées par la signature
 * HMAC du header x-token, jamais par un jeton stocké en base.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cinetpay_transactions', function (Blueprint $table) {
            $table->dropColumn('notify_token');
        });
    }

    public function down(): void
    {
        Schema::table('cinetpay_transactions', function (Blueprint $table) {
            $table->string('notify_token')->nullable()->after('cpm_trans_id');
        });
    }
};
