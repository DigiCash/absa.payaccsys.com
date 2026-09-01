<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Transport;

/**
 * Immutable transport configuration for the ABSA Statements API client.
 *
 * Built from `config('absa.statements')` via `fromConfig()`; an explicit
 * array may be injected (per-call / tests) to bypass the container and the
 * `storage_path()` cert defaults. The optional `retryAttempts` / `retryDelayMs`
 * fields drive transient-failure retries (5xx / connection errors).
 */
final readonly class StatementsApiClientConfig
{
    public function __construct(
        public readonly string $baseUrl,
        public readonly ?string $apiKey = null,
        public readonly ?string $certPath = null,
        public readonly ?string $keyPath = null,
        public readonly ?string $passphrase = null,
        public readonly int $retryAttempts = 0,
        public readonly int $retryDelayMs = 250,
    ) {
    }

    /**
     * @param  array<string, mixed>|null    $override
     */
    public static function fromConfig(?array $override = null): self
    {
        $data = $override ?? config('absa.statements', []);

        return new self(
            baseUrl:       isset($data['base_url'])       ? (string) $data['base_url']       : 'https://api.absa.co.za/statements/v1',
            apiKey:        isset($data['api_key'])        ? (string) $data['api_key']        : null,
            certPath:      isset($data['cert_path'])      ? (string) $data['cert_path']      : null,
            keyPath:       isset($data['key_path'])       ? (string) $data['key_path']       : null,
            passphrase:    isset($data['passphrase'])     ? (string) $data['passphrase']     : null,
            retryAttempts: isset($data['retry_attempts']) ? (int)    $data['retry_attempts'] : 0,
            retryDelayMs:  isset($data['retry_delay_ms']) ? (int)    $data['retry_delay_ms']  : 250,
        );
    }
}
