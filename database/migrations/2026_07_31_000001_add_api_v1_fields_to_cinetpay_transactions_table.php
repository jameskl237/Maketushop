<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cinetpay_transactions', function (Blueprint $table) {
            $table->string('site_id')->nullable()->change();
            $table->string('country', 2)->nullable()->after('site_id');
            $table->string('notify_token')->nullable()->after('cpm_trans_id');
            $table->string('payment_token')->nullable()->after('notify_token');
            $table->string('payment_url')->nullable()->after('payment_token');
        });
    }

    public function down(): void
    {
        Schema::table('cinetpay_transactions', function (Blueprint $table) {
            $table->dropColumn(['country', 'notify_token', 'payment_token', 'payment_url']);
            $table->string('site_id')->nullable(false)->change();
        });
    }
};
