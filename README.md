<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Assistant IA WhatsApp (Botisimo)

Chaque boutique au plan Standard peut activer un assistant WhatsApp qui répond à ses clients à partir de son catalogue, et lancer des campagnes marketing. Toutes les boutiques partagent la clé API et le solde de crédits de Maketu Shop.

L'API Botisimo couvre uniquement WhatsApp Business : ni Messenger, ni Instagram, ni TikTok, ni la génération de contenu. L'interface `App\Services\Channels\ChannelProvider` et la colonne `channel` permettront d'ajouter ces canaux via d'autres API.

### Configuration

Dans `.env` (la clé reste côté serveur, plan Business ou Entreprise requis) :

| Variable | Rôle |
|---|---|
| `BOTISIMO_API_KEY` | Clé `bts_...` de Maketu Shop |
| `BOTISIMO_BASE_URL` | URL de base de l'API |
| `BOTISIMO_DEFAULT_MODEL` | Modèle IA des nouveaux bots, non modifiable ensuite |
| `BOTISIMO_MONTHLY_CREDIT_QUOTA` | Quota mensuel par boutique (1000 par défaut), ajustable par boutique dans l'admin |
| `BOTISIMO_LOW_BALANCE_THRESHOLD` | Seuil d'alerte du solde global (5000 par défaut) |
| `APP_URL` | URL publique en **HTTPS**, obligatoire pour les webhooks et le retour Meta |

Derrière un proxy TLS, renseignez aussi `TRUSTED_PROXIES`. Le reste des réglages est dans `config/botisimo.php`.

Puis appliquez les migrations :

```bash
php artisan migrate
```

En local, exposez l'application avec un tunnel HTTPS et mettez son URL dans `APP_URL`. Sans HTTPS, l'activation s'arrête avant tout appel à Botisimo.

### Services à faire tourner

Le module ne fonctionne pas sans un worker et le scheduler :

```bash
php artisan queue:work   # synchronisation, webhooks, campagnes
* * * * * php /chemin/artisan schedule:run   # cron système
```

| Tâche planifiée | Fréquence | Rôle |
|---|---|---|
| `botisimo:dispatch-campaigns` | chaque minute | Lance les campagnes arrivées à leur date |
| `botisimo:sync-conversations {shop?}` | chaque heure | Rattrape les webhooks perdus, jamais rejoués par Botisimo |
| `botisimo:enforce-subscriptions` | chaque heure | Désactive les bots dont l'abonnement Standard a pris fin |
| `botisimo:check-balance` | chaque heure | Alerte les administrateurs si le solde est bas |
| `botisimo:sync-catalog {shop?} {--prune}` | chaque nuit à 2 h | Réconcilie le catalogue ; `--prune` supprime les entrées inconnues localement |
| `botisimo:check-token-expiry` | chaque jour | Prévient 7 jours avant l'expiration de la connexion WhatsApp |
| `model:prune` | chaque jour | Efface les événements webhook de plus de 30 jours |

### Webhooks

Chaque bot enregistre `{APP_URL}/webhooks/botisimo/{id}` à son activation. L'URL est exemptée de CSRF : chaque appel est authentifié par `X-Botisimo-Signature`, un HMAC-SHA256 du corps brut. Le secret, affiché une seule fois par Botisimo, est chiffré en base : changer `APP_KEY` oblige à recréer les webhooks.

### Prérequis pour les vendeurs

- un abonnement Standard actif sur la boutique ;
- un compte Meta Business ;
- un numéro qui n'est pas utilisé sur l'application WhatsApp classique.

### Limites de requêtes et crédits

- L'API accepte 60 requêtes par minute pour toute la plateforme. `BotisimoClient` plafonne à 8 requêtes par tranche de 10 secondes, et les jobs de fond à 30 par minute. Au-delà, les jobs se relâchent et les pages demandent de réessayer.
- Un message libre coûte 10 crédits, un destinataire marketing 1 crédit. Le quota de chaque boutique est appliqué avant l'envoi, et `shop_bot_credit_usages` trace ces dépenses.
- La consommation éventuelle des réponses de l'IA n'est ni plafonnée ni tracée par boutique.
- Un POST n'est jamais rejoué après un 500, un 502 ou un timeout, pour éviter un double envoi facturé.

### Tests

```bash
php artisan test tests/Feature/Botisimo
```

Ils simulent l'API avec `Http::fake()`.

### À valider avec une vraie clé

La référence ne documente pas le JSON des réponses ni celui des webhooks. Les hypothèses sont regroupées dans `BotisimoPayload` et `InboxMapper`. Avant la mise en production, vérifiez :

1. les réponses de `POST /v1/bots`, `/v1/webhooks`, `/v1/catalog` et `GET /v1/credits/balance` ;
2. le retour de Meta : Botisimo doit ajouter ses paramètres avec `&`, car `callback_url` contient déjà une query string signée ;
3. le corps réel des webhooks `message.received` et `message.status` ;
4. la présence de `bot_id` dans `GET /v1/clients/:id`, sans laquelle la modification des clients est refusée ;
5. les champs acceptés par `PUT /v1/clients/:id` et l'envoi de `active` dans le catalogue ;
6. une recharge de 500 : `country_code` et `callback_url` sont des hypothèses, et le paiement est réel.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Landing Page MaketuShop

La landing page moderne est implémentée avec Vue 3 + Inertia + Shadcn/UI.

### Structure principale

- `resources/js/Pages/Welcome.vue`
- `resources/js/components/landing/layout/`
- `resources/js/components/landing/sections/`
- `resources/js/components/landing/utils/`

### Lancer en local

```bash
npm install
npm run dev
php artisan serve
```

### Configuration Google OAuth

Renseigner ces variables dans `.env` pour activer la connexion Google :

```bash
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
```

Dans la console Google Cloud, l'URI de redirection autorisee doit correspondre a :

```text
http://localhost/auth/google/callback
```

ou a votre domaine local/reel si `APP_URL` est different.

### Points techniques inclus

- Header sticky avec transition transparent -> solide au scroll.
- Sections modulaires (Hero, Features, Categories, Testimonials, FAQ, Newsletter, Footer).
- Animations scroll reveal (IntersectionObserver).
- Compteurs animés et logos partenaires défilants.
- Code splitting sur les sections de la landing via `defineAsyncComponent`.
- Accessibilité de base (aria labels, focus visibles, alt images, navigation clavier).
