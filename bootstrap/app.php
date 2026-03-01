<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Pusher's channel-auth XHR doesn't include a CSRF token.
        // The route still requires an authenticated session, so excluding CSRF
        // here is standard practice for broadcasting auth endpoints.
        $middleware->validateCsrfTokens(except: ['broadcasting/auth']);

        $middleware->api(append: [
            \App\Http\Middleware\SetApiLocale::class,
        ]);
        $middleware->alias([
            'ensure.active'                   => \App\Http\Middleware\EnsureUserIsActive::class,
            'prevent.complete.without.rating' => \App\Http\Middleware\PreventTechnicianCompleteWithoutRating::class,
            'admin'                           => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'check.pending'                   => \App\Http\Middleware\CheckPendingRequests::class,
            'financial.eligibility'           => \App\Http\Middleware\EnsureFinancialEligibility::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
