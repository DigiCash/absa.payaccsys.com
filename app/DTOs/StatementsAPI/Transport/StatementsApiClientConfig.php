<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Transport;

/**
 * Immutable transport configuration for the ABSA Statements API client.
 *
 * Built from `config('absa.statements')` via `fromConfig()`; an explicit
 * array may be injected (per-call / tests) to bypass the container and the
 * `storage_path()` cert defaults. The optional `retryAttempts` / `retryDelayMs`
 * fields drive transient-failure retries (5xx / connection errors). The mTLS
 * material is a single p12 certificate (`cert_path`) with an optional
 * `passphrase` — no separate key file is needed. The bearer token is NOT part
 * of this object — it is read from the runtime config slot
 * `absa.statements.api_key` at call time (see `StatementsApiClient`).
 */
final readonly class StatementsApiClientConfig
{
    public function __construct(
        public readonly string $baseUrl,
        public readonly ?string $certPath = null,
        public readonly ?string $passphrase = null,
        public readonly int $retryAttempts = 0,
        public readonly int $retryDelayMs = 250,
    ) {}

    /**
     * @param  array<string, mixed>|null  $override
     */
    public static function fromConfig(?array $override = null): self
    {
        $data = $override ?? config('absa.statements', []);

        return new self(
            baseUrl: isset($data['base_url']) ? (string) $data['base_url'] : 'https://api.absa.africa/cheque-statements/v1.0',
            certPath: isset($data['cert_path']) ? (string) $data['cert_path'] : null,
            passphrase: isset($data['passphrase']) ? (string) $data['passphrase'] : null,
            retryAttempts: isset($data['retry_attempts']) ? (int) $data['retry_attempts'] : 0,
            retryDelayMs: isset($data['retry_delay_ms']) ? (int) $data['retry_delay_ms'] : 250,
        );
    }

    /**
     * mTLS options for `Http::withOptions()`, using the standard Guzzle option
     * key (`cert`) which Laravel's HttpClient forwards to both the Guzzle and
     * cURL drivers (see ADR-003).
     *
     * `cert` is emitted as a bare path string when no passphrase is configured,
     * or as a `[path, passphrase]` pair when one is — the shape Guzzle expects.
     * The p12 bundle carries its own key, so no separate `ssl_key` option is
     * emitted. When no mTLS material is configured the result is an empty array,
     * so `withOptions([])` is a no-op.
     *
     * @return array<string, string|array{0: string, 1: string}>
     */
    public function sslOptions(): array
    {
        $options = [];

        if ($this->certPath !== null) {
            $options['cert'] = $this->passphrase !== null
                ? [$this->certPath, $this->passphrase]
                : $this->certPath;
        }

        return $options;
    }
}
