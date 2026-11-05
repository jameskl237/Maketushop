<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_bot_campaigns', function (Blueprint $table) {
            $table->string('botisimo_campaign_id')->nullable()->after('channel')->index();
            $table->text('error')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('shop_bot_campaigns', function (Blueprint $table) {
            $table->dropIndex(['botisimo_campaign_id']);
            $table->dropColumn(['botisimo_campaign_id', 'error']);
        });
    }
};
