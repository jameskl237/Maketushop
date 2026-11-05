<?php

namespace App\Services\Botisimo;

use App\Services\Botisimo\Exceptions\BotisimoException;

/** La campagne ne peut pas partir : le motif est affiché tel quel au vendeur. */
class CampaignRejected extends BotisimoException
{
    public function userMessage(): string
    {
        return $this->getMessage();
    }
}
