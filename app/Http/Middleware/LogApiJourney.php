<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class LogApiJourney
{
    /** Sensitive fields to redact from log input payloads. */
    private const array SENSITIVE_FIELDS = [
        'password',
        'password_confirmation',
        'secret',
        'client_secret',
        'token',
        'api_key',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        // Ensure a unique correlation ID exists for this request thread
        if (!$request->hasHeader('X-Request-ID')) {
            $request->headers->set('X-Request-ID', (string)Str::uuid());
        }

        /** @var Response $response */
        $response = $next($request);

        $durationMs = round((microtime(true) - $startTime) * 1000, 2);
        $user = $request->user();

        Log::info(sprintf('API %s %s [%d] (%sms)', $request->method(), $request->path(), $response->getStatusCode(), $durationMs), [
            'user_id' => $user?->getAuthIdentifier(),
            'user_email' => $user?->email ?? null,
            'method' => $request->method(),
            'path' => $request->path(),
            'status' => $response->getStatusCode(),
            'duration_ms' => $durationMs,
            'ip' => $request->ip(),
            'payload' => $request->except(self::SENSITIVE_FIELDS),
        ]);

        return $response;
    }
}

