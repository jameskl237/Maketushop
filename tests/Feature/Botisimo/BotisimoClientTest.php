<?php

namespace Tests\Feature\Botisimo;

use App\Services\Botisimo\BotisimoClient;
use App\Services\Botisimo\Exceptions\BotisimoAuthenticationException;
use App\Services\Botisimo\Exceptions\BotisimoConfigurationException;
use App\Services\Botisimo\Exceptions\BotisimoConflictException;
use App\Services\Botisimo\Exceptions\BotisimoConnectionException;
use App\Services\Botisimo\Exceptions\BotisimoException;
use App\Services\Botisimo\Exceptions\BotisimoForbiddenException;
use App\Services\Botisimo\Exceptions\BotisimoMetaException;
use App\Services\Botisimo\Exceptions\BotisimoNotFoundException;
use App\Services\Botisimo\Exceptions\BotisimoQuotaExceededException;
use App\Services\Botisimo\Exceptions\BotisimoRateLimitException;
use App\Services\Botisimo\Exceptions\BotisimoServerException;
use App\Services\Botisimo\Exceptions\BotisimoValidationException;
use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Sleep;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BotisimoClientTest extends TestCase
{
    private const BASE = 'https://botisimo.test/api';

    private const KEY = 'bts_secret_key';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'botisimo.api_key' => self::KEY,
            'botisimo.base_url' => self::BASE.'/',
            'botisimo.retry.max_attempts' => 3,
            'botisimo.retry.base_delay_ms' => 500,
            'botisimo.retry.max_wait_seconds' => 5,
            'botisimo.rate_limit.max_requests' => 100,
            'botisimo.rate_limit.window_seconds' => 10,
        ]);

        Sleep::fake();
    }

    private function client(): BotisimoClient
    {
        return app(BotisimoClient::class);
    }

    public static function endpoints(): array
    {
        return [
            'listBots' => [fn ($c) => $c->listBots(), 'GET', '/v1/bots'],
            'getBot' => [fn ($c) => $c->getBot('b1'), 'GET', '/v1/bots/b1'],
            'createBot' => [fn ($c) => $c->createBot(['name' => 'x']), 'POST', '/v1/bots', ['name' => 'x']],
            'updateBot' => [fn ($c) => $c->updateBot('b1', ['status' => 'active']), 'PUT', '/v1/bots/b1', ['status' => 'active']],
            'deleteBot' => [fn ($c) => $c->deleteBot('b1'), 'DELETE', '/v1/bots/b1'],
            'activateBot' => [fn ($c) => $c->activateBot('b1'), 'POST', '/v1/bots/b1/activate'],
            'deactivateBot' => [fn ($c) => $c->deactivateBot('b1'), 'POST', '/v1/bots/b1/deactivate'],
            'connectBot' => [fn ($c) => $c->connectBot('b1', 'https://x/cb'), 'POST', '/v1/bots/b1/connect', ['callback_url' => 'https://x/cb']],
            'sendMessage' => [fn ($c) => $c->sendMessage('b1', ['to' => '237']), 'POST', '/v1/messages/b1/send', ['to' => '237']],
            'sendMarketing' => [fn ($c) => $c->sendMarketing(['bot_id' => 'b1']), 'POST', '/v1/marketing/send', ['bot_id' => 'b1']],
            'broadcast' => [fn ($c) => $c->broadcast(['bot_id' => 'b1']), 'POST', '/v1/marketing/broadcast', ['bot_id' => 'b1']],
            'listCampaigns' => [fn ($c) => $c->listCampaigns(['bot_id' => 'b1', 'limit' => 100]), 'GET', '/v1/marketing/campaigns?bot_id=b1&limit=100'],
            'listConversations' => [fn ($c) => $c->listConversations(['bot_id' => 'b1', 'status' => null]), 'GET', '/v1/conversations?bot_id=b1'],
            'getConversation' => [fn ($c) => $c->getConversation('c1'), 'GET', '/v1/conversations/c1'],
            'listConversationMessages' => [fn ($c) => $c->listConversationMessages('c1'), 'GET', '/v1/conversations/c1/messages'],
            'setConversationAi' => [fn ($c) => $c->setConversationAi('c1', false), 'PUT', '/v1/conversations/c1/ai', ['ai_enabled' => false]],
            'listTemplates' => [fn ($c) => $c->listTemplates('b1'), 'GET', '/v1/templates?bot_id=b1'],
            'createTemplate' => [fn ($c) => $c->createTemplate(['name' => 'promo']), 'POST', '/v1/templates', ['name' => 'promo']],
            'deleteTemplate' => [fn ($c) => $c->deleteTemplate('t1'), 'DELETE', '/v1/templates/t1'],
            'listCatalog' => [fn ($c) => $c->listCatalog('b1', ['limit' => 200, 'offset' => 0]), 'GET', '/v1/catalog?bot_id=b1&limit=200&offset=0'],
            'createCatalogProduct' => [fn ($c) => $c->createCatalogProduct(['name' => 'x']), 'POST', '/v1/catalog', ['name' => 'x']],
            'updateCatalogProduct' => [fn ($c) => $c->updateCatalogProduct('p1', ['price' => 10]), 'PUT', '/v1/catalog/p1', ['price' => 10]],
            'deleteCatalogProduct' => [fn ($c) => $c->deleteCatalogProduct('p1'), 'DELETE', '/v1/catalog/p1'],
            'setCatalogAi' => [fn ($c) => $c->setCatalogAi('b1', true), 'PUT', '/v1/catalog/b1/ai', ['enabled' => true]],
            'listClients' => [fn ($c) => $c->listClients(['search' => 'awa']), 'GET', '/v1/clients?search=awa'],
            'getClient' => [fn ($c) => $c->getClient('u1'), 'GET', '/v1/clients/u1'],
            'createClient' => [fn ($c) => $c->createClient(['phone' => '237']), 'POST', '/v1/clients', ['phone' => '237']],
            'updateClient' => [fn ($c) => $c->updateClient('u1', ['name' => 'x']), 'PUT', '/v1/clients/u1', ['name' => 'x']],
            'deleteClient' => [fn ($c) => $c->deleteClient('u1'), 'DELETE', '/v1/clients/u1'],
            'createWebhook' => [fn ($c) => $c->createWebhook('b1', 'https://x/w', ['message.received']), 'POST', '/v1/webhooks', ['bot_id' => 'b1', 'url' => 'https://x/w', 'events' => ['message.received']]],
            'listWebhooks' => [fn ($c) => $c->listWebhooks(), 'GET', '/v1/webhooks'],
            'deleteWebhook' => [fn ($c) => $c->deleteWebhook('b1'), 'DELETE', '/v1/webhooks/b1'],
            'creditBalance' => [fn ($c) => $c->creditBalance(), 'GET', '/v1/credits/balance'],
            'rechargeCredits' => [fn ($c) => $c->rechargeCredits(['amount' => 500]), 'POST', '/v1/credits/recharge', ['amount' => 500]],
        ];
    }

    #[DataProvider('endpoints')]
    public function test_endpoint_calls_documented_route(Closure $call, string $method, string $path, ?array $body = null): void
    {
        Http::fake(['*' => Http::response(['ok' => true])]);

        $this->assertSame(['ok' => true], $call($this->client()));

        Http::assertSent(function (Request $request) use ($method, $path, $body) {
            return $request->method() === $method
                && $request->url() === self::BASE.$path
                && $request->header('x-api-key') === [self::KEY]
                && ($body === null || $request->data() === $body);
        });
    }

    public function test_every_public_method_of_the_client_is_covered(): void
    {
        $methods = collect((new \ReflectionClass(BotisimoClient::class))->getMethods(\ReflectionMethod::IS_PUBLIC))
            ->filter(fn (\ReflectionMethod $method) => $method->class === BotisimoClient::class)
            ->map(fn (\ReflectionMethod $method) => $method->name)
            ->sort()->values()->all();

        $covered = collect(array_keys(self::endpoints()))->sort()->values()->all();

        $this->assertSame($covered, $methods);
    }

    public function test_path_segments_are_encoded(): void
    {
        Http::fake(['*' => Http::response([])]);

        $this->client()->getBot('a/b');

        Http::assertSent(fn (Request $r) => $r->url() === self::BASE.'/v1/bots/a%2Fb');
    }

    public static function errors(): array
    {
        return [
            [400, BotisimoValidationException::class],
            [401, BotisimoAuthenticationException::class],
            [403, BotisimoForbiddenException::class],
            [404, BotisimoNotFoundException::class],
            [405, BotisimoException::class],
            [409, BotisimoConflictException::class],
            [422, BotisimoQuotaExceededException::class],
            [429, BotisimoRateLimitException::class],
            [500, BotisimoServerException::class],
            [502, BotisimoMetaException::class],
        ];
    }

    #[DataProvider('errors')]
    public function test_each_status_maps_to_a_typed_exception(int $status, string $class): void
    {
        config(['botisimo.retry.max_attempts' => 1]);
        Http::fake(['*' => Http::response(['error' => 'refusé'], $status)]);

        try {
            $this->client()->listBots();
            $this->fail('Exception attendue');
        } catch (BotisimoException $e) {
            $this->assertSame($class, $e::class);
            $this->assertSame($status, $e->status);
        }
    }

    public function test_missing_api_key_makes_no_call(): void
    {
        config(['botisimo.api_key' => '']);
        Http::fake();

        $this->expectException(BotisimoConfigurationException::class);

        try {
            $this->client()->listBots();
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_get_retries_server_errors_with_exponential_backoff(): void
    {
        Http::fakeSequence()->push([], 500)->push([], 502)->push(['ok' => true]);

        $this->assertSame(['ok' => true], $this->client()->listBots());

        Http::assertSentCount(3);
        Sleep::assertSequence([
            Sleep::for(500)->milliseconds(),
            Sleep::for(1000)->milliseconds(),
        ]);
    }

    public function test_post_is_not_retried_on_server_error_to_avoid_double_send(): void
    {
        Http::fake(['*' => Http::response([], 502)]);

        $this->expectException(BotisimoMetaException::class);

        try {
            $this->client()->sendMessage('b1', ['to' => '237']);
        } finally {
            Http::assertSentCount(1);
        }
    }

    public function test_post_is_not_retried_on_timeout(): void
    {
        Http::fake(fn () => throw new ConnectionException('timeout'));

        $this->expectException(BotisimoConnectionException::class);

        try {
            $this->client()->sendMessage('b1', ['to' => '237']);
        } finally {
            Sleep::assertNeverSlept();
        }
    }

    public function test_post_is_retried_on_429_honouring_retry_after(): void
    {
        Http::fakeSequence()->push([], 429, ['Retry-After' => '2'])->push(['ok' => true]);

        $this->assertSame(['ok' => true], $this->client()->sendMessage('b1', ['to' => '237']));

        Http::assertSentCount(2);
        Sleep::assertSequence([Sleep::for(2)->seconds()]);
    }

    public function test_long_retry_after_is_handed_back_to_the_caller(): void
    {
        Http::fake(['*' => Http::response([], 429, ['Retry-After' => '30'])]);

        try {
            $this->client()->listBots();
            $this->fail('Exception attendue');
        } catch (BotisimoRateLimitException $e) {
            $this->assertSame(30, $e->retryAfter);
        }

        Http::assertSentCount(1);
        Sleep::assertNeverSlept();
    }

    public function test_platform_budget_blocks_before_calling_the_api(): void
    {
        config(['botisimo.rate_limit.max_requests' => 2]);
        RateLimiter::clear('botisimo:api');
        Http::fake(['*' => Http::response([])]);

        $this->client()->listBots();
        $this->client()->listBots();

        try {
            $this->client()->listBots();
            $this->fail('Exception attendue');
        } catch (BotisimoRateLimitException $e) {
            $this->assertNull($e->status);
            $this->assertGreaterThan(0, $e->retryAfter);
        }

        Http::assertSentCount(2);
        Sleep::assertNeverSlept();
    }

    public function test_default_rate_limit_stays_under_sixty_per_rolling_minute(): void
    {
        $config = require config_path('botisimo.php');
        $windows = (int) ceil(60 / $config['rate_limit']['window_seconds']) + 1;

        $this->assertLessThan(60, $windows * $config['rate_limit']['max_requests']);
    }

    public function test_logs_never_contain_the_key_or_the_payload(): void
    {
        $logged = [];
        Log::listen(function ($event) use (&$logged) {
            $logged[] = $event->message.json_encode($event->context, JSON_UNESCAPED_SLASHES);
        });
        Http::fake(['*' => Http::response(['error' => 'x'], 400)]);

        try {
            $this->client()->sendMessage('b1', ['to' => '237699000000', 'message' => 'bonjour']);
        } catch (BotisimoValidationException) {
            // attendu
        }

        $output = implode("\n", $logged);
        $this->assertStringContainsString('/v1/messages/b1/send', $output);
        $this->assertStringNotContainsString(self::KEY, $output);
        $this->assertStringNotContainsString('237699000000', $output);
    }
}
