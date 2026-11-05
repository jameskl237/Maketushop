<?php

namespace App\Services\Botisimo\Exceptions;

/** 429 distant, ou budget global de la plateforme épuisé avant l'envoi. */
class BotisimoRateLimitException extends BotisimoException
{
    public function __construct(
        string $message = 'Limite de requêtes Botisimo atteinte.',
        ?int $status = 429,
        ?\Throwable $previous = null,
        public readonly int $retryAfter = 10,
    ) {
        parent::__construct($message, $status, $previous);
    }
}
