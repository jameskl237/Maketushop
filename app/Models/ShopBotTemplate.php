<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopBotTemplate extends Model
{
    public const STATUS_PENDING = 'PENDING';

    public const STATUS_APPROVED = 'APPROVED';

    public const STATUS_REJECTED = 'REJECTED';

    protected $fillable = [
        'shop_bot_id',
        'channel',
        'botisimo_template_id',
        'name',
        'category',
        'language',
        'status',
        'components',
    ];

    protected function casts(): array
    {
        return ['components' => 'array'];
    }

    public function body(): string
    {
        return (string) (collect($this->components)->firstWhere('type', 'BODY')['text'] ?? '');
    }

    public function variableCount(): int
    {
        return count(self::variablesIn($this->body()));
    }

    /** @return array<int, int> numéros des variables {{n}}, triés et sans doublon */
    public static function variablesIn(string $text): array
    {
        preg_match_all('/\{\{(\d+)\}\}/', $text, $matches);

        $numbers = array_unique(array_map('intval', $matches[1]));
        sort($numbers);

        return $numbers;
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(ShopBot::class, 'shop_bot_id');
    }
}
