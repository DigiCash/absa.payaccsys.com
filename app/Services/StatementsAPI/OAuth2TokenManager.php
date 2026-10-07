<?php

declare(strict_types=1);

namespace App\Services\StatementsAPI;

use App\DTOs\StatementsAPI\Errors\ErrorResponseDTO;
use App\DTOs\StatementsAPI\Responses\OAuth\OAuthTokenResponseDTO;
use App\DTOs\StatementsAPI\Transport\TokenAcquisitionException;
use App\Services\StatementsAPI\Contracts\OAuth2TokenManagerInterface;
use App\Traits\InteractsWithDatabaseLog;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Concrete {@see OAuth2TokenManagerInterface} for the ABSA Statements API.
 *
 * Resolves a usable bearer token for outbound calls via the single credential
 * strategy from ADR-001:
 *
 *  - OAuth2 Resource Owner Password Credentials (RFC 6749): when `client_id`,
 *    `username` and `password` are configured, a token is POSTed
 *    (`grant_type=password`) to `oauth_token_url` over the mTLS certificate
 *    (`cert_path` / `passphrase`) and cached under `token_cache_key` for
 *    `expires_in - token_ttl_buffer` seconds, so repeated calls reuse the
 *    cached token instead of hitting the token endpoint.
 *
 * A non-2xx token response, an undecodable body, or missing password-grant
 * credentials is reported as a {@see TokenAcquisitionException}. There is no
 * static-key fallback — ABSA requires a real OAuth2 bearer token.
 */
final class OAuth2TokenManager implements OAuth2TokenManagerInterface
{
    use InteractsWithDatabaseLog;

    /**
     * Default cache key, mirroring `config('absa.statements.token_cache_key')`.
     *
     * @var string
     */
    private const string DEFAULT_CACHE_KEY = 'absa.statements.oauth_token';

    /**
     * Default safety buffer (seconds) subtracted from `expires_in`.
     *
     * @var int
     */
    private const int DEFAULT_TTL_BUFFER = 60;

    /**
     * Logger Name
     */
    protected string $loggerName = 'ABSA API - OAuth2TokenManager';

    public function __construct(
        private readonly ?string $oauthTokenUrl = null,
        private readonly ?string $clientId = null,
        private readonly ?string $scope = null,
        private readonly ?string $username = null,
        private readonly ?string $password = null,
        private readonly ?string $certPath = null,
        private readonly ?string $certPassphrase = null,
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
            scope: isset($data['scope']) ? (string) $data['scope'] : null,
            username: isset($data['username']) ? (string) $data['username'] : null,
            password: isset($data['password']) ? (string) $data['password'] : null,
            certPath: isset($data['cert_path']) ? (string) $data['cert_path'] : null,
            certPassphrase: isset($data['passphrase']) ? (string) $data['passphrase'] : null,
            tokenCacheKey: isset($data['token_cache_key']) ? (string) $data['token_cache_key'] : self::DEFAULT_CACHE_KEY,
            tokenTtlBuffer: isset($data['token_ttl_buffer']) ? (int) $data['token_ttl_buffer'] : self::DEFAULT_TTL_BUFFER,
        );
    }

    public function getValidToken(): string
    {
        // 1. Reuse a cached token when one is present and non-empty.
        $cached = Cache::get($this->tokenCacheKey);

        if (is_string($cached) && $cached !== '') {
            $this->logDb->debug('Returning cached token');

            return $cached;
        }

        // 2. Missing OAuth password-grant credentials → fail fast (no static
        //    key fallback — ABSA requires a real OAuth2 token).
        if ($this->clientId === null || $this->username === null || $this->password === null) {
            throw new TokenAcquisitionException(
                status: 0,
                error: null,
            );
        }

        // 3. OAuth2 Resource Owner Password flow, cached for the remaining lifetime.
        return $this->acquireAndCache();
    }

    /**
     * POST the Resource Owner Password grant, parse the token, cache it for the
     * remaining lifetime and return the access token.
     *
     * @throws ConnectionException
     */
    private function acquireAndCache(): string
    {
        $this->logDb->debug('Acquiring New token...',
            [
                'authTokenURL' => $this->oauthTokenUrl,
                'grant_type' => 'password',
                'client_id' => $this->clientId,
                'scope' => $this->scope,
                'has_username' => $this->username !== null,
                'has_password' => $this->password !== null,
            ]
        );

        $request = Http::asForm();
        $sslOptions = $this->sslOptions();

        if (! empty($sslOptions)) {
            $request = $request->withOptions($sslOptions);
        }

        $response = $request->post($this->oauthTokenUrl, array_filter([
            'grant_type' => 'password',
            'client_id' => $this->clientId,
            'scope' => $this->scope,
            'username' => $this->username,
            'password' => $this->password,
        ], static fn ($value) => $value !== null));

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

        $this->logDb->debug('Access Token: '.$dto->accessToken);

        return $dto->accessToken;
    }

    /**
     * mTLS options for the token request, using the standard Guzzle `cert` key
     * (ADR-003). The p12 certificate path is emitted either bare or as a
     * `[path, passphrase]` pair when a passphrase is configured. When no mTLS
     * material is configured, an empty array is returned so the token request
     * runs without client-certificate options.
     *
     * @return array<string, string|array{0: string, 1: string}>
     */
    private function sslOptions(): array
    {
        $options = [];

        if ($this->certPath !== null) {
            $options['cert'] = $this->certPassphrase !== null
                ? [$this->certPath, $this->certPassphrase]
                : $this->certPath;
        }

        return $options;
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

    /**
     * Keep the context for logging purposes.
     * Not really necessary, but let's do this through
     * the development lifecycle
     */
    public function toLogContext(): array
    {
        return [
            'oauth_token_url' => $this->oauthTokenUrl,
            'client_id' => $this->clientId,
            'scope' => $this->scope,
            'token_cache_key' => $this->tokenCacheKey,
            'token_ttl_buffer' => $this->tokenTtlBuffer,
            'has_username' => ! empty($this->username),
            'has_password' => ! empty($this->password),
            'has_cert_path' => ! empty($this->certPath),
        ];
    }
}
