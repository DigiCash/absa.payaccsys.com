<?php

namespace App\Services\StatementsAPI\Support;

use App\DTOs\StatementsAPI\Transport\TokenAcquisitionException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Redacts secrets and PII before the audit row is written.
 * This is a pure helper class with no external dependencies.
 */
class AuditLogSanitizer
{
    /**
     * Sanitize request headers and payload, removing sensitive fields.
     */
    public function sanitize(array $headers, array $payload): array
    {
        $sanitizedHeaders = $this->sanitizeHeaders($headers);
        $sanitizedPayload = $this->sanitizePayload($payload);

        return [
            'headers' => $sanitizedHeaders,
            'payload' => $sanitizedPayload,
        ];
    }

    /**
     * Sanitize response data for audit logging.
     */
    public function sanitizeResponse(Response $response): array
    {
        $body = json_decode($response->getContent(), true) ?? null;

        return [
            'status' => $response->getStatusCode(),
            'headers' => $response->headers->all(),
            'body' => $this->sanitizePayload($body),
        ];
    }

    /**
     * Redact sensitive fields from headers.
     */
    private function sanitizeHeaders(array $headers): array
    {
        $sensitiveKeys = ['Authorization', 'api_key', 'client_secret', 'X-API-Key', 'X-Secret', 'Password', 'token'];
        $sanitized = [];

        foreach ($headers as $key => $values) {
            $key = strtoupper($key);

            if (in_array($key, $sensitiveKeys, true)) {
                $sanitized[$key] = ['***REDACTED***'];
            } else {
                $sanitized[$key] = $values;
            }
        }

        return $sanitized;
    }

    /**
     * Redact sensitive fields from payload.
     */
    private function sanitizePayload(array $payload): array
    {
        if (! is_array($payload)) {
            return $payload;
        }

        $sensitiveKeys = ['api_key', 'client_secret', 'password', 'token', 'bearer', 'authorization', 'secret', 'key'];

        array_walk_recursive($payload, function (&$value, $key) use ($sensitiveKeys) {
            $key = strtolower($key);

            if (in_array($key, $sensitiveKeys, true)) {
                $value = '***REDACTED***';
            }
        });

        return $payload;
    }
}