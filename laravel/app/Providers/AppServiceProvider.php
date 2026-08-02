<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());

        $this->rateLimits();
    }

    /**
     * Nothing on the API was capped before this, which left first launch free to
     * mint accounts and signing in free to guess passwords, neither with a limit.
     */
    private function rateLimits(): void
    {
        // The everyday ceiling. Generous, because a phone catching up on a day
        // of memories is a burst of ordinary requests, not an attack.
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(300)
            ->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())));

        // Signing in and first launch, where 300 a minute would be no cap at all.
        //
        // Keyed on the email as well as the address, so a run of guesses at one
        // account cannot lock out everybody else behind the same NAT. First launch
        // carries no email, so the empty key caps new accounts per address instead.
        RateLimiter::for('auth', fn (Request $request) => [
            Limit::perMinute(5)->by(Str::lower((string) $request->input('email')).'|'.$request->ip()),
            Limit::perMinute(30)->by((string) $request->ip()),
        ]);
    }
}
