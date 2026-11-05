<?php

return [
    // Clé unique de Maketu Shop (plan Business ou Entreprise) : jamais exposée au frontend.
    'api_key' => env('BOTISIMO_API_KEY'),
    'base_url' => env('BOTISIMO_BASE_URL', 'https://xzbcnenulykdstlvbgly.supabase.co/functions/v1/api-gateway'),
    'default_model' => env('BOTISIMO_DEFAULT_MODEL', 'gpt-6-luna'),

    'timeout' => (int) env('BOTISIMO_TIMEOUT', 15),
    'connect_timeout' => (int) env('BOTISIMO_CONNECT_TIMEOUT', 5),

    'retry' => [
        'max_attempts' => (int) env('BOTISIMO_RETRY_MAX_ATTEMPTS', 3),
        'base_delay_ms' => (int) env('BOTISIMO_RETRY_BASE_DELAY_MS', 500),
        // Au-delà, on n'attend pas dans le processus : l'appelant reçoit
        // BotisimoRateLimitException et un job peut se relâcher plus tard.
        'max_wait_seconds' => (int) env('BOTISIMO_RETRY_MAX_WAIT_SECONDS', 5),
    ],

    // Budget commun à toute la plateforme. 8 requêtes par fenêtre fixe de 10 s
    // garantissent au plus 56 requêtes sur n'importe quelle minute glissante
    // (60 s chevauchent 7 fenêtres), sous la limite Botisimo de 60/min.
    'rate_limit' => [
        'max_requests' => (int) env('BOTISIMO_RATE_LIMIT_MAX_REQUESTS', 8),
        'window_seconds' => (int) env('BOTISIMO_RATE_LIMIT_WINDOW_SECONDS', 10),
        // Part des jobs de fond : le reste du budget (48/min) garde les pages du
        // vendeur réactives pendant une grosse synchronisation.
        'background_per_minute' => (int) env('BOTISIMO_RATE_LIMIT_BACKGROUND_PER_MINUTE', 30),
    ],

    'context_message_limit' => 10,

    'human_transfer_keywords' => ['réclamation', 'plainte', 'remboursement', 'conseiller', 'humain'],

    'webhook_events' => ['message.received', 'message.status'],

    'webhook_event_retention_days' => 30,

    'token_expiry_warning_days' => 7,

    // Temps laissé au vendeur pour terminer le signup Meta avant que le lien de retour n'expire.
    'callback_ttl_minutes' => 60,

    'credits' => [
        'monthly_quota' => (int) env('BOTISIMO_MONTHLY_CREDIT_QUOTA', 1000),
        'low_balance_threshold' => (int) env('BOTISIMO_LOW_BALANCE_THRESHOLD', 5000),
        'message_cost' => 10,
        'marketing_cost' => 1,
    ],
];
