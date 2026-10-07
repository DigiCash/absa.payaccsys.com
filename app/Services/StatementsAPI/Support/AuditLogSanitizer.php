<?php

namespace App\Services\StatementsAPI\Support;

use Illuminate\Http\Response;

/**
 * Redacts secrets and PII before the audit row is written.
 * This is a pure helper class with no external dependencies.
 */
class AuditLogSanitizer
{
    /** Header names that must never be persisted in an audit row. */
    private const array SENSITIVE_HEADERS = [
        'AUTHORIZATION',
        'API_KEY',
        'X-API-KEY',
        'X-SECRET',
        'CLIENT_SECRET',
        'PASSWORD',
        'TOKEN',
    ];

    /** Payload keys that must never be persisted in an audit row. */
    private const array SENSITIVE_PAYLOAD_KEYS = [
        'api_key',
        'client_secret',
        'password',
        'token',
        'refresh_token',
        'bearer',
        'authorization',
        'secret',
    ];

    /**
     * Sanitize request headers and payload, removing sensitive fields.
     */
    public function sanitize(array $headers, ?array $payload): array
    {
        return [
            'headers' => $this->sanitizeHeaders($headers),
            'payload' => $this->sanitizePayload($payload),
        ];
    }

    /**
     * Sanitize response data for audit logging.
     */
    public function sanitizeResponse(Response $response): array
    {
        $decoded = json_decode($response->getContent(), true);
        $body = is_array($decoded) ? $decoded : $response->getContent();

        return [
            'status' => $response->getStatusCode(),
            'headers' => $response->headers->all(),
            'body' => $this->sanitizePayload($body),
        ];
    }

    /**
     * Redact sensitive fields from headers.
     *
     * HTTP header names are case-insensitive, so the output keys are normalised
     * to UPPERCASE for deterministic, case-insensitive comparisons downstream.
     */
    private function sanitizeHeaders(array $headers): array
    {
        $sanitized = [];

        foreach ($headers as $key => $values) {
            $normalized = strtoupper($key);

            $sanitized[$normalized] = in_array($normalized, self::SENSITIVE_HEADERS, true)
                ? ['***REDACTED***']
                : $values;
        }

        return $sanitized;
    }

    /**
     * Redact sensitive fields from a payload (recursively).
     */
    private function sanitizePayload(mixed $payload): mixed
    {
        if (! is_array($payload)) {
            return $payload;
        }

        array_walk_recursive($payload, function (&$value, $key) {
            if (is_string($key) && in_array(strtolower($key), self::SENSITIVE_PAYLOAD_KEYS, true)) {
                $value = '***REDACTED***';
            }
        });

        return $payload;
    }
}
