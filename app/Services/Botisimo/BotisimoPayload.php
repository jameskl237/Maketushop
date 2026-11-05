<?php

namespace App\Services\Botisimo;

use Illuminate\Support\Carbon;

/**
 * Lecture des réponses Botisimo. La référence ne documente pas leur enveloppe :
 * c'est le seul endroit à corriger une fois les formats réels confirmés.
 */
final class BotisimoPayload
{
    public static function get(array $response, string ...$keys): mixed
    {
        foreach ($keys as $key) {
            foreach ([$key, "data.{$key}", "bot.{$key}", "data.bot.{$key}"] as $path) {
                $value = data_get($response, $path);

                if ($value !== null && $value !== '') {
                    return $value;
                }
            }
        }

        return null;
    }

    /** Accepte une date ISO ou un timestamp ; null si elle est illisible. */
    public static function date(mixed $value): ?Carbon
    {
        try {
            return is_numeric($value) ? Carbon::createFromTimestamp((int) $value) : ($value ? Carbon::parse($value) : null);
        } catch (\Throwable) {
            return null;
        }
    }

    /** @return array<int, mixed>|null null si aucune liste n'a été trouvée */
    public static function list(array $response, string ...$keys): ?array
    {
        if (array_is_list($response)) {
            return $response;
        }

        foreach ($keys as $key) {
            $value = self::get($response, $key);

            if (is_array($value) && array_is_list($value)) {
                return $value;
            }
        }

        return null;
    }
}
