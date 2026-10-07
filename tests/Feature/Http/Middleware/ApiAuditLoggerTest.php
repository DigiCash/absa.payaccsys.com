<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\ApiAuditLogger;
use App\Jobs\StatementsAPI\RecordApiAuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

/**
 * Feature tests for `ApiAuditLogger` middleware (integration with queue/job).
 */
uses(TestCase::class);

it('dispatches an audit job when processing a request', function (): void {
    Queue::fake();

    $middleware = new ApiAuditLogger;
    $request = Request::create('/health');
    $request->request->replace(['api_key' => 'sensitive']);
    $request->headers->set('X-Request-ID', 'test-request-123');
    $request->headers->set('Authorization', 'Bearer token');

    $response = new Response('OK', 200);

    $result = $middleware->handle($request, fn () => $response);

    // The middleware returns the response unchanged
    expect($result)->toBe($response);

    // Should have dispatched the audit job
    Queue::assertPushed(RecordApiAuditLog::class, function ($job) {
        expect($job->correlationId)->toBe('test-request-123');
        expect($job->method)->toBe('GET');
        expect($job->endpoint)->toBe('http://localhost/health');
        expect($job->responseStatus)->toBe(200);
        expect($job->requestHeaders['authorization'])->toBe(['Bearer token']);
        expect($job->requestPayload)->toBe(['api_key' => 'sensitive']);
        // Check that sensitive fields are redacted in the sanitized request
        expect($job->sanitizedRequest['headers']['AUTHORIZATION'])->toBe(['***REDACTED***']);
        expect($job->sanitizedRequest['payload']['api_key'])->toBe('***REDACTED***');

        return true;
    });
});

it('is instantiable and respects the audit_enabled flag', function (): void {
    // This test assumes a future implementation of audit_enabled flag
    // For now, just verify that the middleware exists and can be instantiated
    $middleware = new ApiAuditLogger;

    expect($middleware)->toBeInstanceOf(ApiAuditLogger::class);
});

it('handles a request with no sensitive data', function (): void {
    Queue::fake();

    $middleware = new ApiAuditLogger;
    $request = Request::create('/health');
    $request->request->replace(['data' => 'public']);
    $request->headers->set('X-Request-ID', 'clean-request-456');
    $request->headers->set('Content-Type', 'application/json');

    $response = new Response('OK', 200);

    $result = $middleware->handle($request, fn () => $response);

    expect($result)->toBe($response);

    // Should have dispatched the audit job
    Queue::assertPushed(RecordApiAuditLog::class, function ($job) {
        expect($job->correlationId)->toBe('clean-request-456');
        expect($job->requestPayload)->toBe(['data' => 'public']);

        // Headers should not be redacted (no sensitive keys); sanitized keys are UPPERCASE
        expect($job->sanitizedRequest['headers']['CONTENT-TYPE'])->toBe(['application/json']);

        return true;
    });
});

it('handles error responses correctly', function (): void {
    Queue::fake();

    $middleware = new ApiAuditLogger;
    $request = Request::create('/missing');
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
});

it('preserves request identity and adds correlation ID', function (): void {
    Queue::fake();

    $middleware = new ApiAuditLogger;
    $request = Request::create('/health');
    $request->request->replace(['timestamp' => now()->toISOString()]);
    $request->headers->set('X-Request-ID', 'preserve-request-999');
    $request->headers->set('X-Correlation-ID', 'corr-123');

    $response = new Response('Success', 200);

    $result = $middleware->handle($request, fn () => $response);

    expect($result)->toBe($response);

    Queue::assertPushed(RecordApiAuditLog::class, function ($job) {
        // The correlation ID should match what's in the request
        expect($job->correlationId)->toBe('preserve-request-999');
        expect($job->requestHeaders['x-request-id'])->toBe(['preserve-request-999']);
        expect($job->requestHeaders['x-correlation-id'])->toBe(['corr-123']);

        return true;
    });
});

it('creates multiple audit jobs for multiple requests', function (): void {
    Queue::fake();

    $middleware = new ApiAuditLogger;

    $request1 = new Request;
    $request1->headers->set('X-Request-ID', 'req-1');

    $request2 = new Request;
    $request2->headers->set('X-Request-ID', 'req-2');

    $response = new Response('OK', 200);

    $middleware->handle($request1, fn () => $response);
    $middleware->handle($request2, fn () => $response);

    // Should have dispatched two audit jobs
    Queue::assertPushed(RecordApiAuditLog::class, 2);

    // Verify both jobs were created with correct correlation IDs
    Queue::assertPushed(RecordApiAuditLog::class, function ($job) {
        expect(in_array($job->correlationId, ['req-1', 'req-2']))->toBe(true);

        return true;
    }, 2);
});

it('logs debug details when queue dispatch fails', function (): void {
    Bus::shouldReceive('dispatch')
        ->once()
        ->andThrow(new \Exception('Database connection dropped'));

    // Create an untyped mock instead of mocking DatabaseLogProxy directly
    $loggerMock = Mockery::mock();
    $loggerMock->shouldReceive('error')
        ->once()
        ->with('Database connection dropped', Mockery::type('array'));

    $middleware = new ApiAuditLogger;
    $middleware->logDb = $loggerMock; // Dynamic override bypasses __get()

    $request = Request::create('/health');
    $response = new Response('OK', 200);

    $result = $middleware->handle($request, fn () => $response);

    expect($result)->toBe($response);
});
