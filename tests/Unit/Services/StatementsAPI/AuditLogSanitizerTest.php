<?php

namespace Tests\Unit\Services\StatementsAPI;

use App\Services\StatementsAPI\Support\AuditLogSanitizer;
use Tests\TestCase;

/**
 * Unit tests for `AuditLogSanitizer` (pure function, no dependencies).
 */
class AuditLogSanitizerTest extends TestCase
{
    /**
     * The sanitizer redacts sensitive headers.
     */
    public function test_sanitize_redacts_sensitive_headers(): void
    {
        $sanitizer = new AuditLogSanitizer();

        $headers = [
            'Authorization' => ['Bearer secret-token'],
            'api_key' => ['key123'],
            'X-API-Key' => ['xkey'],
            'Content-Type' => ['application/json'],
            'X-Secret' => ['top-secret'],
            'client_secret' => ['secret456'],
            'X-Request-ID' => ['req-123'],
        ];

        $payload = ['data' => 'normal'];

        $sanitized = $sanitizer->sanitize($headers, $payload);

        expect($sanitized['headers']['AUTHORIZATION'])->toBe(['***REDACTED***']);
        expect($sanitized['headers']['API_KEY'])->toBe(['***REDACTED***']);
        expect($sanitized['headers']['X-API-KEY'])->toBe(['***REDACTED***']);
        expect($sanitized['headers']['X-SECRET'])->toBe(['***REDACTED***']);
        expect($sanitized['headers']['CLIENT_SECRET'])->toBe(['***REDACTED***']);

        expect($sanitized['headers']['CONTENT-TYPE'])->toBe(['application/json']);
        expect($sanitized['headers']['X-REQUEST-ID'])->toBe(['req-123']);

        expect($sanitized['payload'])->toBe(['data' => 'normal']);
    }

    /**
     * The sanitizer redacts sensitive fields in payload.
     */
    public function test_sanitize_redacts_sensitive_payload(): void
    {
        $sanitizer = new AuditLogSanitizer();

        $headers = ['Content-Type' => ['application/json']];
        $payload = [
            'api_key' => 'key123',
            'client_secret' => 'secret456',
            'password' => 'password123',
            'token' => 'bearer-token',
            'bearer' => 'bearer-value',
            'authorization' => 'Bearer secret',
            'secret' => 'top-secret',
            'key' => 'normal-key',
            'public_data' => 'visible',
        ];

        $sanitized = $sanitizer->sanitize($headers, $payload);

        expect($sanitized['payload']['api_key'])->toBe('***REDACTED***');
        expect($sanitized['payload']['client_secret'])->toBe('***REDACTED***');
        expect($sanitized['payload']['password'])->toBe('***REDACTED***');
        expect($sanitized['payload']['token'])->toBe('***REDACTED***');
        expect($sanitized['payload']['bearer'])->toBe('***REDACTED***');
        expect($sanitized['payload']['authorization'])->toBe('***REDACTED***');
        expect($sanitized['payload']['secret'])->toBe('***REDACTED***');

        expect($sanitized['payload']['key'])->toBe('normal-key');
        expect($sanitized['payload']['public_data'])->toBe('visible');
    }

    /**
     * The sanitizer handles nested payload structures.
     */
    public function test_sanitize_redacts_nested_payload(): void
    {
        $sanitizer = new AuditLogSanitizer();

        $headers = ['Content-Type' => ['application/json']];
        $payload = [
            'user' => [
                'api_key' => 'user-key',
                'profile' => ['name' => 'John Doe', 'email' => 'john@example.com'],
                'settings' => [
                    'auth' => [
                        'token' => 'nested-token',
                        'refresh_token' => 'refresh-me',
                    ],
                ],
            ],
        ];

        $sanitized = $sanitizer->sanitize($headers, $payload);

        expect($sanitized['payload']['user']['api_key'])->toBe('***REDACTED***');
        expect($sanitized['payload']['user']['settings']['auth']['token'])->toBe('***REDACTED***');
        expect($sanitized['payload']['user']['settings']['auth']['refresh_token'])->toBe('***REDACTED***');

        expect($sanitized['payload']['user']['profile']['name'])->toBe('John Doe');
        expect($sanitized['payload']['user']['profile']['email'])->toBe('john@example.com');
    }

    /**
     * The sanitizer handles null/undefined values gracefully.
     */
    public function test_sanitize_handles_null_values(): void
    {
        $sanitizer = new AuditLogSanitizer();

        $headers = [];
        $payload = null;

        $sanitized = $sanitizer->sanitize($headers, $payload);

        expect($sanitized['headers'])->toBe([]);
        expect($sanitized['payload'])->toBe(null);
    }

    /**
     * The sanitizer handles empty arrays.
     */
    public function test_sanitize_handles_empty_arrays(): void
    {
        $sanitizer = new AuditLogSanitizer();

        $headers = [];
        $payload = [];

        $sanitized = $sanitizer->sanitize($headers, $payload);

        expect($sanitized['headers'])->toBe([]);
        expect($sanitized['payload'])->toBe([]);
    }

    /**
     * Sanitize response handles successful response.
     */
    public function test_sanitize_response_with_success(): void
    {
        $sanitizer = new AuditLogSanitizer();

        $response = new \Illuminate\Http\Response(
            json_encode(['status' => 'success', 'api_key' => 'secret', 'data' => 'value']),
            200,
            ['Content-Type' => 'application/json']
        );

        $sanitized = $sanitizer->sanitizeResponse($response);

        expect($sanitized['status'])->toBe(200);
        expect($sanitized['headers']['content-type'])->toBe(['application/json']);
        expect($sanitized['body']['status'])->toBe('success');
        expect($sanitized['body']['api_key'])->toBe('***REDACTED***');
        expect($sanitized['body']['data'])->toBe('value');
    }

    /**
     * Sanitize response handles error response.
     */
    public function test_sanitize_response_with_error(): void
    {
        $sanitizer = new AuditLogSanitizer();

        $response = new \Illuminate\Http\Response(
            json_encode(['error' => 'Invalid credentials', 'code' => 401]),
            401,
            ['Content-Type' => 'application/json', 'X-Error' => 'true']
        );

        $sanitized = $sanitizer->sanitizeResponse($response);

        expect($sanitized['status'])->toBe(401);
        expect($sanitized['headers']['content-type'])->toBe(['application/json']);
        expect($sanitized['headers']['x-error'])->toBe(['true']);
        expect($sanitized['body']['error'])->toBe('Invalid credentials');
        expect($sanitized['body']['code'])->toBe(401);
    }

    /**
     * Sanitize response handles non-JSON response.
     */
    public function test_sanitize_response_with_non_json(): void
    {
        $sanitizer = new AuditLogSanitizer();

        $response = new \Illuminate\Http\Response(
            'Internal Server Error',
            500,
            ['Content-Type' => 'text/plain']
        );

        $sanitized = $sanitizer->sanitizeResponse($response);

        expect($sanitized['status'])->toBe(500);
        expect($sanitized['headers']['content-type'])->toBe(['text/plain']);
        expect($sanitized['body'])->toBe('Internal Server Error');
    }
}
