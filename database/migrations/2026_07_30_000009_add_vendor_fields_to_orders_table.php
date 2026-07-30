<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('vendor_status')->default('pending')->after('status');
            $table->unsignedBigInteger('platform_fee')->default(0)->after('total_price');
            $table->unsignedBigInteger('vendor_amount')->default(0)->after('platform_fee');
            $table->timestamp('vendor_accepted_at')->nullable()->after('vendor_status');
            $table->timestamp('vendor_preparing_at')->nullable()->after('vendor_accepted_at');
            $table->timestamp('vendor_shipped_at')->nullable()->after('vendor_preparing_at');
            $table->timestamp('vendor_delivered_at')->nullable()->after('vendor_shipped_at');
            $table->timestamp('escrow_released_at')->nullable()->after('vendor_delivered_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'vendor_status', 'platform_fee', 'vendor_amount',
                'vendor_accepted_at', 'vendor_preparing_at',
                'vendor_shipped_at', 'vendor_delivered_at', 'escrow_released_at',
            ]);
        });
    }
};
