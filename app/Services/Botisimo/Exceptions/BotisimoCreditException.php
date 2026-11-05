<?php

namespace App\Services\Botisimo\Exceptions;

/** Quota de la boutique ou solde de la plateforme insuffisant : aucun envoi n'est tenté. */
class BotisimoCreditException extends BotisimoException
{
    public function userMessage(): string
    {
        return $this->getMessage();
    }
}
