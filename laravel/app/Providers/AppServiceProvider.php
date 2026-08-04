<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\Settings;
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
        // One instance for the request, so the numbers that pace the app are read
        // from the table once however many times they are asked for. Scoped rather
        // than singleton: a queue worker stays booted between jobs, and a settings
        // change made on the console should reach the next job, not the next deploy.
        $this->app->scoped(Settings::class);
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

        // Photos are not API calls. One album screen asks for a thumbnail and a
        // display copy per memory, and this route carries no token — so it keys on
        // the address, which a whole household, or a whole carrier NAT, shares.
        // Capped well above what scrolling costs and separately from the API, so a
        // photo grid cannot spend somebody else's allowance.
        RateLimiter::for('media', fn (Request $request) => Limit::perMinute(1200)->by((string) $request->ip()));

        // A six-character code is short enough to guess at if guessing is free. A
        // parent adds a grandparent twice in a lifetime, so ten a minute is plenty.
        RateLimiter::for('share', fn (Request $request) => Limit::perMinute(10)
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
