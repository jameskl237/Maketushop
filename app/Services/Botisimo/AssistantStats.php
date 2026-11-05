<?php

namespace App\Services\Botisimo;

use App\Models\ShopBot;
use App\Models\ShopBotCampaign;
use App\Models\ShopBotCreditUsage;
use App\Models\ShopBotMessage;

/** Statistiques calculées sur la copie locale : aucune requête vers Botisimo. */
class AssistantStats
{
    public function __construct(private CreditLedger $credits) {}

    public function for(ShopBot $bot): array
    {
        $shop = $bot->shop;
        $messages = ShopBotMessage::whereIn('shop_bot_conversation_id', $bot->conversations()->select('id'));

        return [
            'conversations' => $bot->conversations()->count(),
            'conversations_30d' => $bot->conversations()->where('last_message_at', '>=', now()->subDays(30))->count(),
            'clients' => $bot->conversations()->whereNotNull('client_phone')->distinct()->count('client_phone'),
            'messages_inbound' => (clone $messages)->where('direction', ShopBotMessage::DIRECTION_INBOUND)->count(),
            'messages_outbound' => (clone $messages)->where('direction', ShopBotMessage::DIRECTION_OUTBOUND)->count(),
            'campaigns' => $bot->campaigns()->where('status', ShopBotCampaign::STATUS_SENT)->count(),
            'campaign_recipients' => (int) $bot->campaigns()->sum('sent'),
            'credits_month' => $this->credits->usedThisMonth($shop),
            'credits_total' => (int) ShopBotCreditUsage::where('shop_id', $shop->id)->sum('credits'),
            'credits_quota' => $this->credits->quota($shop),
        ];
    }
}
