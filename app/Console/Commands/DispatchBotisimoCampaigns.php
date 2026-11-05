<?php

namespace App\Console\Commands;

use App\Jobs\Botisimo\SendCampaign;
use App\Models\ShopBotCampaign;
use Illuminate\Console\Command;

class DispatchBotisimoCampaigns extends Command
{
    protected $signature = 'botisimo:dispatch-campaigns';

    protected $description = 'Lance les campagnes WhatsApp arrivées à leur date prévue';

    public function handle(): int
    {
        ShopBotCampaign::where('status', ShopBotCampaign::STATUS_SCHEDULED)
            ->where('scheduled_at', '<=', now())
            ->pluck('id')
            ->each(fn ($id) => SendCampaign::dispatch($id));

        return self::SUCCESS;
    }
}
