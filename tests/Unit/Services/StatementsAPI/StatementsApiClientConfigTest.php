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

it('resolves base_url and the mTLS material from an override array', function () {
    $cfg = StatementsApiClientConfig::fromConfig([
        'base_url' => 'https://example.test/statements/v1',
        'cert_path' => '/tmp/certs/absa.p12',
        'passphrase' => 's3cr3t',
    ]);

    expect($cfg->baseUrl)->toBe('https://example.test/statements/v1');
    expect($cfg->certPath)->toBe('/tmp/certs/absa.p12');
    expect($cfg->passphrase)->toBe('s3cr3t');
});

it('defaults mTLS material to null when absent', function () {
    $cfg = StatementsApiClientConfig::fromConfig([
        'base_url' => 'https://example.test/statements/v1',
    ]);

    expect($cfg->baseUrl)->toBe('https://example.test/statements/v1');
    expect($cfg->certPath)->toBeNull();
    expect($cfg->passphrase)->toBeNull();
});

it('falls back to the default base_url when none is supplied', function () {
    $cfg = StatementsApiClientConfig::fromConfig([]);

    expect($cfg->baseUrl)->toBe('https://api.absa.africa/cheque-statements/v1.0');
});

it('maps the mTLS p12 cert to the standard Guzzle sslOptions (cert as string)', function () {
    $cfg = StatementsApiClientConfig::fromConfig([
        'cert_path' => '/tmp/certs/absa.p12',
    ]);

    expect($cfg->sslOptions())->toBe([
        'cert' => '/tmp/certs/absa.p12',
    ]);
});

it('pairs cert with its passphrase as a [path, passphrase] tuple when a passphrase is set', function () {
    $cfg = StatementsApiClientConfig::fromConfig([
        'cert_path' => '/tmp/certs/absa.p12',
        'passphrase' => 's3cr3t',
    ]);

    expect($cfg->sslOptions())->toBe([
        'cert' => ['/tmp/certs/absa.p12', 's3cr3t'],
    ]);
});

it('returns an empty sslOptions array when no mTLS material is configured', function () {
    $cfg = StatementsApiClientConfig::fromConfig([
        'base_url' => 'https://example.test/statements/v1',
    ]);

    expect($cfg->sslOptions())->toBe([]);
});
