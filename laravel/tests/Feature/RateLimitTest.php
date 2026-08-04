<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

it('caps a run of guesses at one account', function () {
    User::factory()->create(['email' => 'a@b.com']);

    foreach (range(1, 5) as $ignored) {
        $this->postJson('/api/v1/auth/login', ['email' => 'a@b.com', 'password' => 'wrong'])
            ->assertStatus(422);
    }

    $this->postJson('/api/v1/auth/login', ['email' => 'a@b.com', 'password' => 'wrong'])
        ->assertStatus(429);
});

it('leaves the neighbours alone when one account is being guessed at', function () {
    User::factory()->create(['email' => 'a@b.com']);
    User::factory()->create(['email' => 'c@d.com']);

    foreach (range(1, 6) as $ignored) {
        $this->postJson('/api/v1/auth/login', ['email' => 'a@b.com', 'password' => 'wrong']);
    }

    // Same address, different account — the key is the pair, not the address
    // alone, so a whole office behind one NAT is not locked out by one attack.
    $this->postJson('/api/v1/auth/login', ['email' => 'c@d.com', 'password' => 'password'])
        ->assertOk();
});

it('caps how many accounts one address can open on first launch', function () {
    foreach (range(1, 5) as $ignored) {
        $this->postJson('/api/v1/auth/guest')->assertCreated();
    }

    $this->postJson('/api/v1/auth/guest')->assertStatus(429);
});

it('leaves no api route uncapped', function () {
    // The cap rides on the api group rather than on the routes, so the stack has
    // to be resolved the way the router resolves it before anything is asserted.
    // Which limiter is not the point here — that none is missing entirely is.
    $router = app(Router::class);

    $uncapped = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => str_starts_with($route->uri(), 'api/'))
        ->reject(fn ($route) => collect($router->gatherRouteMiddleware($route))
            ->contains(fn ($middleware) => is_string($middleware)
                && str_starts_with($middleware, ThrottleRequests::class.':')))
        ->map(fn ($route) => $route->uri())
        ->unique()
        ->values();

    expect($uncapped)->toBeEmpty();
});

it('counts photos on their own meter, not the api one', function () {
    $router = app(Router::class);

    $media = collect(Route::getRoutes()->getRoutes())
        ->first(fn ($route) => str_starts_with($route->uri(), 'api/v1/media'));

    $throttles = collect($router->gatherRouteMiddleware($media))
        ->filter(fn ($middleware) => is_string($middleware) && str_starts_with($middleware, ThrottleRequests::class.':'))
        ->values();

    expect($throttles->all())->toBe([ThrottleRequests::class.':media']);
});

it('sets the everyday cap at 300 a minute, and photos well above it', function () {
    $limiter = app(RateLimiter::class)->limiter('api');
    $media = app(RateLimiter::class)->limiter('media');

    expect($limiter(Request::create('/api/v1/children'))->maxAttempts)->toBe(300)
        ->and($media(Request::create('/api/v1/media/abc'))->maxAttempts)->toBe(1200);
});
