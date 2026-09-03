<?php

declare(strict_types=1);

namespace App\Services\StatementsAPI;

use App\DTOs\StatementsAPI\Errors\ErrorResponseDTO;
use App\DTOs\StatementsAPI\Responses\OAuth\OAuthTokenResponseDTO;
use App\DTOs\StatementsAPI\Transport\TokenAcquisitionException;
use App\Services\StatementsAPI\Contracts\OAuth2TokenManagerInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Concrete {@see OAuth2TokenManagerInterface} for the ABSA Statements API.
 *
 * Resolves a usable bearer token for outbound calls, owning the two credential
 * strategies from ADR-001:
 *
 *  - OAuth2 Client Credentials (RFC 6749): when `client_id` / `client_secret`
 *    are configured, a token is POSTed to `oauth_token_url` and cached under
 *    `token_cache_key` for `expires_in - token_ttl_buffer` seconds, so repeated
 *    calls reuse the cached token instead of hitting the token endpoint.
 *  - Static `api_key` fallback: when OAuth credentials are absent, the static
 *    `api_key` is returned as-is (no HTTP, no cache write).
 *
 * A non-2xx token response, an undecodable body, or a missing token with no
 * fallback is reported as a {@see TokenAcquisitionException}.
 */
final class OAuth2TokenManager implements OAuth2TokenManagerInterface
{
    /** Default cache key, mirroring `config('absa.statements.token_cache_key')`. */
    private const string DEFAULT_CACHE_KEY = 'absa.statements.oauth_token';

    /** Default safety buffer (seconds) subtracted from `expires_in`. */
    private const int DEFAULT_TTL_BUFFER = 60;

    public function __construct(
        private readonly ?string $oauthTokenUrl = null,
        private readonly ?string $clientId = null,
        private readonly ?string $clientSecret = null,
        private readonly ?string $apiKey = null,
        private readonly string $tokenCacheKey = self::DEFAULT_CACHE_KEY,
        private readonly int $tokenTtlBuffer = self::DEFAULT_TTL_BUFFER,
    ) {}

    /**
     * Build the manager from `config('absa.statements')`, or from an explicit
     * override array (per-call / tests) that bypasses the container.
     *
     * @param  array<string, mixed>|null  $override
     */
    public static function fromConfig(?array $override = null): self
    {
        $data = $override ?? config('absa.statements', []);

        return new self(
            oauthTokenUrl: isset($data['oauth_token_url']) ? (string) $data['oauth_token_url'] : null,
            clientId: isset($data['client_id']) ? (string) $data['client_id'] : null,
            clientSecret: isset($data['client_secret']) ? (string) $data['client_secret'] : null,
            apiKey: isset($data['api_key']) ? (string) $data['api_key'] : null,
            tokenCacheKey: isset($data['token_cache_key']) ? (string) $data['token_cache_key'] : self::DEFAULT_CACHE_KEY,
            tokenTtlBuffer: isset($data['token_ttl_buffer']) ? (int) $data['token_ttl_buffer'] : self::DEFAULT_TTL_BUFFER,
        );
    }

    public function getValidToken(): string
    {
        // 1. Reuse a cached token when one is present and non-empty.
        $cached = Cache::get($this->tokenCacheKey);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        // 2. No OAuth credentials → static api_key fallback (no HTTP, no cache).
        if ($this->clientId === null || $this->clientSecret === null) {
            return $this->resolveApiKeyFallback();
        }

        // 3. OAuth2 Client Credentials flow, cached for the remaining lifetime.
        return $this->acquireAndCache();
    }

    /**
     * Resolve the static `api_key` fallback, or fail when no credential at all
     * is available.
     */
    private function resolveApiKeyFallback(): string
    {
        if ($this->apiKey === null) {
            throw new TokenAcquisitionException(
                status: 0,
                error: null,
            );
        }

        return $this->apiKey;
    }

    /**
     * POST the Client Credentials grant, parse the token, cache it for the
     * remaining lifetime and return the access token.
     */
    private function acquireAndCache(): string
    {
        $response = Http::asForm()->post($this->oauthTokenUrl, [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        if (! $response->successful()) {
            throw new TokenAcquisitionException(
                status: $response->status(),
                error: $this->decodeError($response),
            );
        }

        /** @var array<string, mixed> $body */
        $body = $response->json() ?? [];
        $dto = OAuthTokenResponseDTO::fromArray($body);

        if ($dto->accessToken === null) {
            throw new TokenAcquisitionException(
                status: $response->status(),
                error: null,
            );
        }

        $ttl = $this->computeTtl($dto->expiresIn);

        if ($ttl > 0) {
            Cache::put($this->tokenCacheKey, $dto->accessToken, $ttl);
        }

        return $dto->accessToken;
    }

    /**
     * Remaining cache lifetime: `expires_in - token_ttl_buffer`, floored at 0 so
     * a token that is already (near) expired is not cached.
     */
    private function computeTtl(?int $expiresIn): int
    {
        return max(0, ($expiresIn ?? 0) - $this->tokenTtlBuffer);
    }

    /**
     * Decode a non-2xx token body into an `ErrorResponseDTO` when it is JSON.
     */
    private function decodeError(Response $response): ?ErrorResponseDTO
    {
        $body = $response->json();

        return is_array($body) ? ErrorResponseDTO::fromArray($body) : null;
    }
}
