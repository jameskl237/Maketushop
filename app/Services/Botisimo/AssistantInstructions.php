<?php

namespace App\Services\Botisimo;

use App\Models\ShopBot;

/** Génère les ai_instructions à partir des informations de la boutique. */
class AssistantInstructions
{
    public function for(ShopBot $bot): string
    {
        $shop = $bot->shop;
        $language = $bot->language === 'en' ? 'anglais' : 'français';

        $lines = [
            "Tu es l'assistant de la boutique « {$shop->name} » sur la marketplace Maketu Shop.",
            filled($shop->description) ? "Présentation de la boutique : {$shop->description}" : null,
            "Zone de la boutique : {$shop->district}, {$shop->city}.",
            filled($bot->delivery_policy) ? "Politique de livraison : {$bot->delivery_policy}" : null,
            filled($bot->return_policy) ? "Politique de retour : {$bot->return_policy}" : null,
            'Ton : '.($bot->tone ?: 'chaleureux et professionnel').'.',
            "Réponds en {$language}, sauf si le client écrit dans une autre langue.",
            'Règles obligatoires :',
            "- N'invente jamais un prix, un stock, un délai ou une caractéristique : utilise uniquement le catalogue.",
            '- Si une information manque, dis-le et propose de transmettre la question à la boutique.',
            '- Pour une réclamation, un remboursement ou un litige, transfère la conversation à un humain.',
            filled($bot->additional_instructions) ? "Instructions supplémentaires du vendeur : {$bot->additional_instructions}" : null,
        ];

        return implode("\n", array_filter($lines));
    }
}
