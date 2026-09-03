<?php

namespace App\Http\Middleware;

use App\Jobs\StatementsAPI\RecordApiAuditLog;
use App\Services\StatementsAPI\Support\AuditLogSanitizer;
use Closure;

/**
 * Middleware that records every outbound call to the api_audit_logs table via a queued job.
 * The sanitized payload/headers (secrets redacted) are injected into the audit row.
 */
class ApiAuditLogger
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        // Record the request/response for audit logging
        $this->recordRequest($request, $response);

        return $response;
    }

    /**
     * Record the request/response details for audit.
     * The actual job dispatch happens asynchronously; this middleware is lightweight.
     */
    private function recordRequest($request, $response): void
    {
        $sanitizer = new AuditLogSanitizer();

        $sanitizedRequest = $sanitizer->sanitize($request->headers->all(), $request->request->all());
        $sanitizedResponse = $sanitizer->sanitizeResponse($response);

        // Dispatch the audit record job for async processing
        RecordApiAuditLog::dispatch(
            $request->header('X-Request-ID') ?? $request->getRequestUri(),
            $request->method(),
            $request->fullUrl(),
            $request->headers->all(),
            $request->request->all(),
            $response->getStatusCode(),
            $response->getContent(),
            $sanitizedRequest,
            $sanitizedResponse,
            $request->ip(),
            $request->userAgent(),
        );
    }
}