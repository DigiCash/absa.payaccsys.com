<?php

declare(strict_types=1);

namespace Tests\Unit\Services\StatementsAPI;

use App\DTOs\StatementsAPI\Transport\StatementsApiClientConfig;

/*
 * Hermetic unit tests for the Statements transport config value object.
 * No DB / no HTTP / no container: `fromConfig()` is exercised via the
 * explicit-array override branch, so `config('absa.statements')` and
 * `storage_path()` are never touched.
 */

it('resolves base_url, api_key and the mTLS material from an override array', function () {
    $cfg = StatementsApiClientConfig::fromConfig([
        'base_url'   => 'https://example.test/statements/v1',
        'api_key'    => 'sk_test_123',
        'cert_path'  => '/tmp/certs/client.pem',
        'key_path'   => '/tmp/certs/client.key',
        'passphrase' => 's3cr3t',
    ]);

    expect($cfg->baseUrl)->toBe('https://example.test/statements/v1');
    expect($cfg->apiKey)->toBe('sk_test_123');
    expect($cfg->certPath)->toBe('/tmp/certs/client.pem');
    expect($cfg->keyPath)->toBe('/tmp/certs/client.key');
    expect($cfg->passphrase)->toBe('s3cr3t');
});

it('defaults apiKey and mTLS material to null when absent', function () {
    $cfg = StatementsApiClientConfig::fromConfig([
        'base_url' => 'https://example.test/statements/v1',
    ]);

    expect($cfg->baseUrl)->toBe('https://example.test/statements/v1');
    expect($cfg->apiKey)->toBeNull();
    expect($cfg->certPath)->toBeNull();
    expect($cfg->keyPath)->toBeNull();
    expect($cfg->passphrase)->toBeNull();
});

it('falls back to the default base_url when none is supplied', function () {
    $cfg = StatementsApiClientConfig::fromConfig([]);

    expect($cfg->baseUrl)->toBe('https://api.absa.co.za/statements/v1');
});
