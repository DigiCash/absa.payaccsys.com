<?php

declare(strict_types=1);

namespace Tests\Unit\Services\StatementsAPI;

use App\DTOs\StatementsAPI\Errors\ErrorResponseDTO;
use App\DTOs\StatementsAPI\Transport\TokenAcquisitionException;
use App\Services\StatementsAPI\OAuth2TokenManager;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/*
 * Behavioural tests for `OAuth2TokenManager` (ADR-001 credential strategies).
 *
 * Both dependencies are isolated: `Http::fake()` removes any real network and
 * the test environment's `CACHE_STORE=array` gives each test a fresh, in-memory
 * store. No DB, no config reads — the manager is built directly via its
 * constructor so the tests never touch `config('absa.statements')`.
 *
 * Covered behaviours:
 *   1. Cache hit short-circuits and returns the token without any HTTP call.
 *   2. Cache miss POSTs the Client Credentials grant, returns the token and
 *      caches it for the remaining lifetime.
 *   3. With no OAuth credentials the static `api_key` is returned as-is (no
 *      HTTP, no cache write).
 *   4. A token whose remaining lifetime is within the TTL buffer is not cached,
 *      so the next call proactively re-acquires.
 *   5. A non-2xx token response is reported as a `TokenAcquisitionException`
 *      carrying the status and decoded error body.
 *   6. With no credential at all (no OAuth creds, no `api_key`) a
 *       `TokenAcquisitionException` with status 0 is thrown.
 */
uses(TestCase::class);

const TOKEN_URL = 'https://api.example.test/oauth/token';
const CACHE_KEY = 'absa.statements.oauth_token';

/**
 * Build a canonical OAuth2 token-endpoint response body. `access_token` and
 * `expires_in` are the fields the manager consumes; `token_type`/`scope` are
 * optional and ignored by the acquisition path.
 */
function tokenResponse(string $accessToken, int $expiresIn, ?string $tokenType = 'Bearer', ?string $scope = null): array
{
    $body = [
        'access_token' => $accessToken,
        'expires_in' => $expiresIn,
        'token_type' => $tokenType,
    ];

    if ($scope !== null) {
        $body['scope'] = $scope;
    }

    return $body;
}

/**
 * Build a canonical ABSA `ErrorResponse` JSON body — the shared 4xx/5xx payload
 * (`Code`/`Message` always present). Mirrors the shape asserted in
 * `StatementsApiClientTest`.
 */
function oauthErrorBody(string $code, string $message, ?string $id = null, ?array $errors = null): array
{
    $body = ['Code' => $code, 'Message' => $message];

    if ($id !== null) {
        $body['Id'] = $id;
    }

    if ($errors !== null) {
        $body['Errors'] = $errors;
    }

    return $body;
}

/**
 * Run a token acquisition that is expected to fail, capturing the thrown
 * `TokenAcquisitionException` so the test can assert on `->status` and the
 * decoded `->error` `ErrorResponseDTO`.
 */
function expectTokenAcquisitionException(
    callable $operation,
    int $expectedStatus,
    ?string $code = null,
    ?string $message = null,
): TokenAcquisitionException {
    try {
        $operation();
    } catch (TokenAcquisitionException $e) {
        expect($e->status)->toBe($expectedStatus);

        if ($code !== null || $message !== null) {
            expect($e->error)->toBeInstanceOf(ErrorResponseDTO::class);
            expect($e->error?->Code)->toBe($code);
            expect($e->error?->Message)->toBe($message);
        }

        return $e;
    }

    throw new \RuntimeException("Expected TokenAcquisitionException (status {$expectedStatus}) was not thrown");
}

// ---------------------------------------------------------------------------
// 1. Cache hit → return cached token, no HTTP
// ---------------------------------------------------------------------------
it('returns a cached token without making an HTTP request', function () {
    Http::fake();
    Cache::put(CACHE_KEY, 'cached-token-abc', 3600);

    $manager = new OAuth2TokenManager(
        oauthTokenUrl: TOKEN_URL,
        clientId: 'client-id',
        clientSecret: 'client-secret',
        tokenCacheKey: CACHE_KEY,
    );

    expect($manager->getValidToken())->toBe('cached-token-abc');

    Http::assertNothingSent();
});

// ---------------------------------------------------------------------------
// 2. Cache miss → POST Client Credentials, return + cache the token
// ---------------------------------------------------------------------------
it('acquires a token over HTTP on a cache miss and caches it', function () {
    Http::fake(fn (Request $request) => Http::response(tokenResponse('fresh-token-xyz', 3600), 200));

    $manager = new OAuth2TokenManager(
        oauthTokenUrl: TOKEN_URL,
        clientId: 'client-id',
        clientSecret: 'client-secret',
        tokenCacheKey: CACHE_KEY,
    );

    expect($manager->getValidToken())->toBe('fresh-token-xyz');

    Http::assertSent(function (Request $request) {
        return $request->method() === 'POST'
             && str_contains($request->url(), 'oauth/token')
             && str_contains((string) $request->body(), 'grant_type=client_credentials');
    });

    expect(Cache::get(CACHE_KEY))->toBe('fresh-token-xyz');
});

// ---------------------------------------------------------------------------
// 3. No OAuth credentials → static api_key fallback (no HTTP, no cache)
// ---------------------------------------------------------------------------
it('falls back to the static api_key when OAuth credentials are absent', function () {
    Http::fake();

    $manager = new OAuth2TokenManager(
        oauthTokenUrl: TOKEN_URL,
        clientId: null,
        clientSecret: null,
        apiKey: 'sk_test_statements',
        tokenCacheKey: CACHE_KEY,
    );

    expect($manager->getValidToken())->toBe('sk_test_statements');

    Http::assertNothingSent();
    expect(Cache::get(CACHE_KEY))->toBeNull();
});

// ---------------------------------------------------------------------------
// 4. Near-expiry token → not cached, so the next call re-acquires
// ---------------------------------------------------------------------------
it('does not cache a near-expiry token so the next call re-acquires', function () {
    Http::fakeSequence()
        ->push(tokenResponse('near-expiry-token', 30), 200)
        ->push(tokenResponse('near-expiry-token-2', 30), 200);

    $manager = new OAuth2TokenManager(
        oauthTokenUrl: TOKEN_URL,
        clientId: 'client-id',
        clientSecret: 'client-secret',
        tokenCacheKey: CACHE_KEY,
        tokenTtlBuffer: 60,
    );

    // First acquisition: expires_in (30) is within the buffer (60), so TTL is 0
    // and the token is deliberately not cached.
    expect($manager->getValidToken())->toBe('near-expiry-token');
    expect(Cache::get(CACHE_KEY))->toBeNull();

    // Second acquisition: cache is still empty, so a fresh token is fetched.
    expect($manager->getValidToken())->toBe('near-expiry-token-2');

    Http::assertSentCount(2);
});

// ---------------------------------------------------------------------------
// 5. Non-2xx token response → TokenAcquisitionException with decoded error
// ---------------------------------------------------------------------------
it('throws a TokenAcquisitionException on a non-2xx token response', function () {
    Http::fake(fn (Request $request) => Http::response(oauthErrorBody('INVALID_CLIENT', 'Invalid client credentials'), 401));

    $manager = new OAuth2TokenManager(
        oauthTokenUrl: TOKEN_URL,
        clientId: 'client-id',
        clientSecret: 'client-secret',
        tokenCacheKey: CACHE_KEY,
    );

    $exception = expectTokenAcquisitionException(
        fn () => $manager->getValidToken(),
        401,
        code: 'INVALID_CLIENT',
        message: 'Invalid client credentials',
    );

    expect($exception->status)->toBe(401);
    expect(Cache::get(CACHE_KEY))->toBeNull();
});

// ---------------------------------------------------------------------------
// 6. No credential at all → TokenAcquisitionException with status 0
// ---------------------------------------------------------------------------
it('throws a TokenAcquisitionException when no credential is available at all', function () {
    Http::fake();

    $manager = new OAuth2TokenManager(
        oauthTokenUrl: TOKEN_URL,
        clientId: null,
        clientSecret: null,
        apiKey: null,
        tokenCacheKey: CACHE_KEY,
    );

    $exception = expectTokenAcquisitionException(
        fn () => $manager->getValidToken(),
        0,
    );

    expect($exception->status)->toBe(0);
    expect($exception->error)->toBeNull();
    Http::assertNothingSent();
});
