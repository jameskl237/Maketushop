<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            require __DIR__.'/../routes/media.php';
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Proxy TLS en production : TRUSTED_PROXIES=* (ou la liste des IP du proxy).
        if ($proxies = env('TRUSTED_PROXIES')) {
            $middleware->trustProxies(at: $proxies === '*' ? '*' : explode(',', $proxies));
        }

        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
        ]);

        // Les notifications CinetPay sont des POST serveur à serveur : pas de session,
        // donc pas de jeton CSRF. Elles sont authentifiées par le HMAC (header x-token).
        $middleware->validateCsrfTokens(except: [
            'webhooks/cinetpay',
            'webhooks/botisimo/*', // authentifié par X-Botisimo-Signature
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (Response $response, \Throwable $exception, Request $request) {
            $status = $response->getStatusCode();

            // En local (APP_DEBUG=true), on garde la page détaillée de Laravel pour les 500 :
            // la page « Oups » générique masquait la vraie cause (ex. MySQL arrêté).
            $hideDetails = ! config('app.debug') || in_array($status, [404, 403], true);

            if ($hideDetails && ! $request->expectsJson() && in_array($status, [404, 403, 500, 503], true)) {
                return Inertia::render('Error', ['status' => $status])
                    ->toResponse($request)
                    ->setStatusCode($status);
            }

            return $response;
        });
    })->create();
