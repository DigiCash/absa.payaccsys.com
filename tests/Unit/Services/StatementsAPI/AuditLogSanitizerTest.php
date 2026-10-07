<?php

declare(strict_types=1);

namespace Tests\Unit\Services\StatementsAPI;

use App\Services\StatementsAPI\Support\AuditLogSanitizer;
use Illuminate\Http\Response;
use Tests\TestCase;

/**
 * Unit tests for `AuditLogSanitizer` (pure function, no dependencies).
 */
uses(TestCase::class);

it('redacts sensitive headers', function (): void {
    $sanitizer = new AuditLogSanitizer;

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
});

it('redacts sensitive fields in the payload', function (): void {
    $sanitizer = new AuditLogSanitizer;

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
});

it('redacts sensitive fields in nested payload structures', function (): void {
    $sanitizer = new AuditLogSanitizer;

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
});

it('handles null/undefined values gracefully', function (): void {
    $sanitizer = new AuditLogSanitizer;

    $headers = [];
    $payload = null;

    $sanitized = $sanitizer->sanitize($headers, $payload);

    expect($sanitized['headers'])->toBe([]);
    expect($sanitized['payload'])->toBe(null);
});

it('handles empty arrays', function (): void {
    $sanitizer = new AuditLogSanitizer;

    $headers = [];
    $payload = [];

    $sanitized = $sanitizer->sanitize($headers, $payload);

    expect($sanitized['headers'])->toBe([]);
    expect($sanitized['payload'])->toBe([]);
});

it('sanitizes a successful response', function (): void {
    $sanitizer = new AuditLogSanitizer;

    $response = new Response(
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
});

it('sanitizes an error response', function (): void {
    $sanitizer = new AuditLogSanitizer;

    $response = new Response(
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
});

it('sanitizes a non-JSON response', function (): void {
    $sanitizer = new AuditLogSanitizer;

    $response = new Response(
        'Internal Server Error',
        500,
        ['Content-Type' => 'text/plain']
    );

    $sanitized = $sanitizer->sanitizeResponse($response);

    expect($sanitized['status'])->toBe(500);
    expect($sanitized['headers']['content-type'])->toBe(['text/plain']);
    expect($sanitized['body'])->toBe('Internal Server Error');
});
