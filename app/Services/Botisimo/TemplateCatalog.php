<?php

namespace App\Services\Botisimo;

use App\Models\ShopBot;
use App\Models\ShopBotTemplate;
use App\Services\Botisimo\Exceptions\BotisimoException;
use App\Services\Botisimo\Exceptions\BotisimoNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TemplateCatalog
{
    private const REFRESH_INTERVAL_SECONDS = 60;

    public function __construct(private BotisimoClient $client) {}

    public static function slug(string $name): string
    {
        return Str::limit(Str::slug($name, '_'), 512, '');
    }

    /** Composants au format Meta. Meta exige un exemple pour chaque variable. */
    public static function components(array $data): array
    {
        $body = ['type' => 'BODY', 'text' => $data['body']];

        if (! empty($data['examples'])) {
            $body['example'] = ['body_text' => [array_values($data['examples'])]];
        }

        $components = [$body];

        if (filled($data['footer'] ?? null)) {
            $components[] = ['type' => 'FOOTER', 'text' => $data['footer']];
        }

        $buttons = array_values(array_filter($data['buttons'] ?? []));

        if ($buttons !== []) {
            $components[] = [
                'type' => 'BUTTONS',
                'buttons' => array_map(fn ($text) => ['type' => 'QUICK_REPLY', 'text' => $text], $buttons),
            ];
        }

        return $components;
    }

    public function create(ShopBot $bot, array $data): ShopBotTemplate
    {
        $components = self::components($data);

        $response = $this->client->createTemplate([
            'bot_id' => $bot->botisimo_bot_id,
            'name' => $data['name'],
            'category' => $data['category'],
            'language' => $data['language'],
            'components' => $components,
        ]);

        return $bot->templates()->create([
            'channel' => $bot->channel,
            'botisimo_template_id' => BotisimoPayload::get($response, 'id', 'template_id'),
            'name' => $data['name'],
            'category' => $data['category'],
            'language' => $data['language'],
            'status' => BotisimoPayload::get($response, 'status') ?? ShopBotTemplate::STATUS_PENDING,
            'components' => $components,
        ]);
    }

    public function delete(ShopBotTemplate $template): void
    {
        if (filled($template->botisimo_template_id)) {
            try {
                $this->client->deleteTemplate($template->botisimo_template_id);
            } catch (BotisimoNotFoundException) {
                // Déjà absent : le résultat voulu.
            }
        }

        $template->delete();
    }

    /** Suit la validation par Meta. Un échec laisse simplement les derniers statuts connus. */
    public function refreshStatuses(ShopBot $bot): void
    {
        if (! Cache::add("botisimo:templates:{$bot->id}", 1, self::REFRESH_INTERVAL_SECONDS)) {
            return;
        }

        try {
            $response = $this->client->listTemplates($bot->botisimo_bot_id);
        } catch (BotisimoException) {
            return;
        }

        foreach (BotisimoPayload::list($response, 'templates', 'items', 'data') ?? [] as $row) {
            if (! is_array($row) || ! is_string($row['name'] ?? null) || ! is_string($row['status'] ?? null)) {
                continue;
            }

            $bot->templates()
                ->where('name', $row['name'])
                ->when(is_string($row['language'] ?? null), fn ($query) => $query->where('language', $row['language']))
                ->update(array_filter([
                    'status' => strtoupper($row['status']),
                    'botisimo_template_id' => isset($row['id']) ? (string) $row['id'] : null,
                ]));
        }
    }
}
