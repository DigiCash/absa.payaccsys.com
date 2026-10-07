<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Responses\OAuth;

use App\DTOs\StatementsAPI\Support\BaseDto;

/**
 * OAuthTokenResponse — the OAuth2 token endpoint (RFC 6749) response body
 * returned by the Client Credentials grant at `statements.oauth_token_url`.
 *
 * The wire format follows the standard OAuth2 snake_case casing
 * (`access_token`, `token_type`, `expires_in`, `scope`); the DTO exposes
 * those as camelCase properties.
 */
final readonly class OAuthTokenResponseDTO extends BaseDto
{
    public function __construct(
        public readonly ?string $accessToken = null,
        public readonly ?string $tokenType = null,
        public readonly ?int $expiresIn = null,
        public readonly ?string $scope = null,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            accessToken: isset($data['access_token']) ? (string) $data['access_token'] : null,
            tokenType: isset($data['token_type']) ? (string) $data['token_type'] : null,
            expiresIn: isset($data['expires_in']) ? (int) $data['expires_in'] : null,
            scope: isset($data['scope']) ? (string) $data['scope'] : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'access_token' => $this->accessToken,
            'token_type' => $this->tokenType,
            'expires_in' => $this->expiresIn,
            'scope' => $this->scope,
        ], static fn ($value) => $value !== null);
    }
}
