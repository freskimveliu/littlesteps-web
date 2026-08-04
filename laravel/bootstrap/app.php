<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('web')->group(__DIR__.'/../routes/admin.php');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Traefik terminates TLS, so trust its forwarded proto/host headers.
        $middleware->trustProxies(at: '*');

        // Every api/* route is capped by the 'api' limiter in AppServiceProvider.
        // The two unauthenticated ones ask for 'auth' on top, in routes/api.php.
        $middleware->throttleApi();

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // The admin is the only session-authenticated surface; the mobile app
        // uses Sanctum tokens and never hits these.
        $middleware->redirectGuestsTo('/admin/login');
        $middleware->redirectUsersTo('/admin');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // A failure comes back in the same envelope as a success, so the app has one
        // shape to read rather than two. `message` and `errors` keep the names and
        // the places Laravel gave them; `data` and `code` join them.
        //
        // The response is edited rather than rebuilt, so a 429 keeps its Retry-After
        // and a 401 keeps its challenge header.
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            if (! $request->is('api/*') || ! $response instanceof JsonResponse) {
                return $response;
            }

            $payload = (array) $response->getData(true);
            $message = $payload['message'] ?? null;

            // Laravel names the class and the id it could not find — a map of the
            // models for anyone poking at the API, and no use to a parent. Route
            // model binding wraps that in a NotFoundHttpException, so both are asked.
            $missing = $e instanceof ModelNotFoundException
                || $e->getPrevious() instanceof ModelNotFoundException;

            if ($missing || in_array($message, [null, '', 'Not Found'], true)) {
                $message = 'We could not find that.';
            }

            $debug = config('app.debug') ? Arr::only($payload, ['exception', 'file', 'line']) : [];

            $response->setData(array_filter([
                'data' => null,
                'message' => $message,
                'errors' => $payload['errors'] ?? null,
                'code' => $response->getStatusCode(),
                'debug' => $debug === [] ? null : $debug,
            ], fn ($value, $key) => $key === 'data' || $value !== null, ARRAY_FILTER_USE_BOTH));

            return $response;
        });
    })->create();
