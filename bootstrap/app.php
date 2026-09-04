<?php

use App\DTOs\StatementsAPI\Transport\StatementsApiException;
use App\DTOs\StatementsAPI\Transport\TokenAcquisitionException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('api')
                ->group(base_path('routes/statements.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(
            fn (Request $request) => $request->is('api/*') ? null : route('login')
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (ConnectionException $e, Request $request): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'error' => [
                    'Code' => 'UPSTREAM_CONNECTION_FAILED',
                    'Message' => 'Unable to connect to upstream service provider.',
                ],
            ], 502);
        });

        $exceptions->render(function (StatementsApiException $e, Request $request): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            // Connection failures carry status 0; anything outside 400–599
            // surfaces as a Bad Gateway.
            $status = $e->status >= 400 && $e->status <= 599 ? $e->status : 502;

            return response()->json([
                'error' => $e->error?->toArray() ?? ['Code' => 'STATEMENTS_API_ERROR', 'Message' => $e->getMessage()],
            ], $status);
        });

        $exceptions->render(function (TokenAcquisitionException $e, Request $request): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            // A failure to obtain our own outbound credential is never the
            // caller's fault — expose it as a Bad Gateway.
            return response()->json([
                'error' => $e->error?->toArray() ?? ['Code' => 'STATEMENTS_API_ERROR', 'Message' => $e->getMessage()],
            ], 502);
        });
    })->create();
