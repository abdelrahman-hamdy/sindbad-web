<?php

namespace App\Providers;

use App\Models\Request;
use App\Observers\RequestObserver;
use App\Policies\RequestPolicy;
use App\Services\Odoo\OdooService;
use App\Services\Odoo\OdooServiceInterface;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(OdooServiceInterface::class, OdooService::class);
    }

    public function boot(): void
    {
        // Register observer
        Request::observe(RequestObserver::class);

        // Register policies
        Gate::policy(Request::class, RequestPolicy::class);

        // Rate limiters
        RateLimiter::for('otp', function (HttpRequest $request) {
            return Limit::perMinutes(10, 3)->by($request->input('phone', $request->ip()));
        });

        RateLimiter::for('api', function (HttpRequest $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('location-update', function (HttpRequest $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });
    }
}
