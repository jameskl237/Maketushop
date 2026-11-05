<?php

namespace App\Services\Botisimo\Exceptions;

use Exception;
use Illuminate\Http\Client\Response;

/**
 * Erreur renvoyée par l'API Botisimo. Le corps de la réponse n'est jamais
 * conservé en entier : il peut contenir des données clients.
 */
class BotisimoException extends Exception
{
    public function __construct(
        string $message,
        public readonly ?int $status = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $status ?? 0, $previous);
    }

    /** Message affichable au vendeur : jamais le texte brut de l'API. */
    public function userMessage(): string
    {
        return match (true) {
            $this instanceof BotisimoConfigurationException => 'L\'assistant IA n\'est pas encore configuré sur la plateforme. Contactez le support.',
            $this instanceof BotisimoRateLimitException => 'Le service est très sollicité. Réessayez dans quelques secondes.',
            $this instanceof BotisimoConnectionException => 'Le service d\'assistant IA est injoignable. Réessayez dans quelques instants.',
            $this instanceof BotisimoMetaException => 'WhatsApp a refusé l\'opération. Réessayez plus tard.',
            default => 'L\'opération n\'a pas abouti. Réessayez ou contactez le support.',
        };
    }

    public static function fromResponse(Response $response): self
    {
        $status = $response->status();
        $apiMessage = $response->json('error') ?? $response->json('message');
        $message = is_string($apiMessage) && $apiMessage !== ''
            ? $apiMessage
            : "Erreur Botisimo (HTTP {$status})";

        $class = match ($status) {
            400 => BotisimoValidationException::class,
            401 => BotisimoAuthenticationException::class,
            403 => BotisimoForbiddenException::class,
            404 => BotisimoNotFoundException::class,
            409 => BotisimoConflictException::class,
            422 => BotisimoQuotaExceededException::class,
            429 => BotisimoRateLimitException::class,
            500 => BotisimoServerException::class,
            502 => BotisimoMetaException::class,
            default => self::class,
        };

        return new $class($message, $status);
    }
}
