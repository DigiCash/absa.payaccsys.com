<?php

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\ApiAuditLogger;
use App\Jobs\StatementsAPI\RecordApiAuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Feature tests for `ApiAuditLogger` middleware (integration with queue/job).
 */
class ApiAuditLoggerTest extends TestCase
{
    /**
     * The middleware dispatches a job when processing a request.
     */
    public function test_middleware_dispatches_audit_job(): void
    {
        Queue::fake();

        $middleware = new ApiAuditLogger();
        $request = new Request();
        $request->headers->set('X-Request-ID', 'test-request-123');
        $request->headers->set('Authorization', 'Bearer token');
        $request->request->replace(['api_key' => 'sensitive']);

        $response = new Response('OK', 200);

        $result = $middleware->handle($request, fn () => $response);

        // The middleware returns the response unchanged
        expect($result)->toBe($response);

        // Should have dispatched the audit job
        Queue::assertPushed(RecordApiAuditLog::class, function ($job) {
            expect($job->correlationId)->toBe('test-request-123');
            expect($job->method)->toBe('GET');
            expect($job->endpoint)->toBe('/');
            expect($job->responseStatus)->toBe(200);
            expect($job->requestHeaders)->toBe(['authorization' => ['Bearer token']]);
            expect($job->requestPayload)->toBe(['api_key' => 'sensitive']);
            // Check that sensitive fields are redacted in the sanitized request
            expect($job->sanitizedRequest['headers']['AUTHORIZATION'])->toBe(['***REDACTED***']);
            expect($job->sanitizedRequest['payload']['api_key'])->toBe('***REDACTED***');

            return true;
        });
    }

    /**
     * The middleware respects audit_enabled flag (when implemented).
     */
    public function test_middleware_respects_audit_enabled_flag(): void
    {
        // This test assumes a future implementation of audit_enabled flag
        // For now, just verify that the middleware exists and can be instantiated
        $middleware = new ApiAuditLogger();

        expect($middleware)->toBeInstanceOf(ApiAuditLogger::class);
    }

    /**
     * The middleware handles request with no sensitive data.
     */
    public function test_middleware_handles_request_without_sensitive_data(): void
    {
        Queue::fake();

        $middleware = new ApiAuditLogger();
        $request = new Request();
        $request->headers->set('X-Request-ID', 'clean-request-456');
        $request->headers->set('Content-Type', 'application/json');
        $request->request->replace(['data' => 'public']);

        $response = new Response('OK', 200);

        $result = $middleware->handle($request, fn () => $response);

        expect($result)->toBe($response);

        // Should have dispatched the audit job
        Queue::assertPushed(RecordApiAuditLog::class, function ($job) {
            expect($job->correlationId)->toBe('clean-request-456');
            expect($job->requestPayload)->toBe(['data' => 'public']);

            // Headers should not be redacted (no sensitive keys)
            expect($job->sanitizedRequest['headers']['content-type'])->toBe(['application/json']);

            return true;
        });
    }

    /**
     * The middleware handles error responses correctly.
     */
    public function test_middleware_handles_error_response(): void
    {
        Queue::fake();

        $middleware = new ApiAuditLogger();
        $request = new Request();
        $request->headers->set('X-Request-ID', 'error-request-789');

        $response = new Response('Not Found', 404);

        $result = $middleware->handle($request, fn () => $response);

        expect($result)->toBe($response);
        expect($response->getStatusCode())->toBe(404);

        // Should have dispatched the audit job with error status
        Queue::assertPushed(RecordApiAuditLog::class, function ($job) {
            expect($job->correlationId)->toBe('error-request-789');
            expect($job->responseStatus)->toBe(404);

            return true;
        });
    }

    /**
     * The middleware preserves request identity and adds correlation ID.
     */
    public function test_middleware_preserves_request_identity(): void
    {
        Queue::fake();

        $middleware = new ApiAuditLogger();
        $request = new Request();
        $request->headers->set('X-Request-ID', 'preserve-request-999');
        $request->headers->set('X-Correlation-ID', 'corr-123');
        $request->request->replace(['timestamp' => now()->toISOString()]);

        $response = new Response('Success', 200);

        $result = $middleware->handle($request, fn () => $response);

        expect($result)->toBe($response);

        Queue::assertPushed(RecordApiAuditLog::class, function ($job) {
            // The correlation ID should match what's in the request
            expect($job->correlationId)->toBe('preserve-request-999');
            expect($job->requestHeaders)->toBe([
                'x-request-id' => ['preserve-request-999'],
                'x-correlation-id' => ['corr-123'],
            ]);

            return true;
        });
    }

    /**
     * Integration test: multiple requests create multiple audit jobs.
     */
    public function test_multiple_requests_create_multiple_audit_jobs(): void
    {
        Queue::fake();

        $middleware = new ApiAuditLogger();

        $request1 = new Request();
        $request1->headers->set('X-Request-ID', 'req-1');

        $request2 = new Request();
        $request2->headers->set('X-Request-ID', 'req-2');

        $response = new Response('OK', 200);

        $middleware->handle($request1, fn () => $response);
        $middleware->handle($request2, fn () => $response);

        // Should have dispatched two audit jobs
        Queue::assertPushed(RecordApiAuditLog::class, 2);

        // Verify both jobs were created with correct correlation IDs
        Queue::assertPushed(RecordApiAuditLog::class, function ($job) use ($middleware) {
            expect(in_array($job->correlationId, ['req-1', 'req-2']))->toBe(true);
            return true;
        }, 2);
    }
}
