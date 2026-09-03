<?php

namespace App\Jobs\StatementsAPI;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Job that records audit log entries to the api_audit_logs table.
 * This job is dispatched asynchronously from ApiAuditLogger middleware.
 */
class RecordApiAuditLog implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The unique ID of this job.
     */
    public ?string $uniqueId = null;

    /**
     * The correlation ID for tracing across services.
     */
    public string $correlationId;

    /**
     * The direction of the call.
     */
    public string $direction;

    /**
     * The service name (e.g., 'statements').
     */
    public string $service;

    /**
     * The HTTP method.
     */
    public string $method;

    /**
     * The full endpoint URL.
     */
    public string $endpoint;

    /**
     * The request headers (sanitized externally).
     */
    public array $requestHeaders;

    /**
     * The request payload (sanitized externally).
     */
    public array $requestPayload;

    /**
     * The HTTP response status code.
     */
    public int $responseStatus;

    /**
     * The response payload (sanitized externally).
     */
    public array $responsePayload;

    /**
     * The latency in milliseconds (if available).
     */
    public ?int $latencyMs;

    /**
     * Exception details (if any).
     */
    public array $exceptionDetails;

    /**
     * The sanitized request data (for audit).
     */
    public array $sanitizedRequest;

    /**
     * The sanitized response data (for audit).
     */
    public array $sanitizedResponse;

    /**
     * The client IP address.
     */
    public string $ipAddress;

    /**
     * The user agent string.
     */
    public string $userAgent;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $correlationId,
        string $direction,
        string $service,
        string $method,
        string $endpoint,
        array $requestHeaders,
        array $requestPayload,
        int $responseStatus,
        array $responsePayload,
        ?int $latencyMs,
        array $exceptionDetails,
        array $sanitizedRequest,
        array $sanitizedResponse,
        string $ipAddress,
        string $userAgent,
    ) {
        $this->correlationId = $correlationId;
        $this->direction = $direction;
        $this->service = $service;
        $this->method = $method;
        $this->endpoint = $endpoint;
        $this->requestHeaders = $requestHeaders;
        $this->requestPayload = $requestPayload;
        $this->responseStatus = $responseStatus;
        $this->responsePayload = $responsePayload;
        $this->latencyMs = $latencyMs;
        $this->exceptionDetails = $exceptionDetails;
        $this->sanitizedRequest = $sanitizedRequest;
        $this->sanitizedResponse = $sanitizedResponse;
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;

        // Set a unique ID based on correlation ID for idempotency
        $this->uniqueId = $correlationId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // The API audit log lives on the application PostgreSQL connection.
        DB::connection('pgsql_main')->table('api_audit_logs')->insert([
            'id' => (string) Str::uuid(),
            'correlation_id' => $this->correlationId,
            'direction' => $this->direction,
            'service' => $this->service,
            'method' => $this->method,
            'endpoint' => $this->endpoint,
            // Only sanitized data is persisted (secrets/PII already redacted).
            'request_headers' => json_encode($this->sanitizedRequest['headers'] ?? []),
            'request_payload' => json_encode($this->sanitizedRequest['payload'] ?? []),
            'response_status' => $this->responseStatus,
            'response_payload' => isset($this->sanitizedResponse['body'])
                ? json_encode($this->sanitizedResponse['body'])
                : null,
            'latency_ms' => $this->latencyMs,
            'exception_details' => json_encode($this->exceptionDetails),
            'environment' => (string) config('absa.environment', 'sandbox'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Get the unique ID for this job.
     */
    public function uniqueId(): ?string
    {
        return $this->uniqueId;
    }

    /**
     * The number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [1, 5, 10];
    }
}
