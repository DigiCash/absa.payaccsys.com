<?php

namespace App\Http\Middleware;

use App\Jobs\StatementsAPI\RecordApiAuditLog;
use App\Services\StatementsAPI\Support\AuditLogSanitizer;
use App\Traits\InteractsWithDatabaseLog;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Middleware that records every outbound call to the api_audit_logs table via a queued job.
 * The sanitized payload/headers (secrets redacted) are injected into the audit row.
 */
class ApiAuditLogger
{
    use InteractsWithDatabaseLog;

    /**
     * Logger Name
     *
     * @var string
     */
    protected string $loggerName = 'ABSA API - ApiAuditLogger';

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response|RedirectResponse)  $next
     * @return Response|RedirectResponse
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
        $sanitizer = new AuditLogSanitizer;

        $sanitizedRequest = $sanitizer->sanitize($request->headers->all(), $request->request->all());
        $sanitizedResponse = $sanitizer->sanitizeResponse($response);

        try {
            // Dispatch the audit record job for async processing
            RecordApiAuditLog::dispatch(
                correlationId: $request->header('X-Request-ID') ?? $request->getRequestUri(),
                direction: 'inbound_facade',
                service: 'statements',
                method: $request->method(),
                endpoint: $request->fullUrl(),
                requestHeaders: $request->headers->all(),
                requestPayload: $request->request->all(),
                responseStatus: $response->getStatusCode(),
                responsePayload: json_decode($response->getContent(), true) ?? [],
                latencyMs: null,
                exceptionDetails: [],
                sanitizedRequest: $sanitizedRequest,
                sanitizedResponse: $sanitizedResponse,
                ipAddress: (string) $request->ip(),
                userAgent: $request->userAgent() ?? '',
            );
        } catch (\Throwable $e) {
            $this->logDb->error($e->getMessage(), ['trace' => $e->getTraceAsString()]);
        }

    }
}
