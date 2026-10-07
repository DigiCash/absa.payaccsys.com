<?php

/**
 * One-off enrichment for the auto-generated OpenAPI document.
 *
 * Scramble (`php artisan scramble:export --path=docs/apis/statements/openapi.json`)
 * produces the structural spec but emits generic `items: {}` for controller 200
 * bodies (serialised via `->json($dto->toArray())`). This script injects:
 *   - `info.title`           → "ABSA API Hub"
 *   - verified response schemas (see {@see StatementsOpenApiSchemas})
 *   - per-operation 200 `$ref`s to those schemas
 *
 * Run (regeneration step after any Scramble re-export):
 *   docker exec absa84_api php artisan tinker --execute 'require(base_path("docs/apis/statements/enrich_openapi.php"));'
 */

declare(strict_types=1);
use App\Support\StatementsOpenApiSchemas;

function __std_to_array(mixed $value): mixed
{
    if ($value instanceof stdClass) {
        $out = [];

        foreach ($value as $key => $item) {
            $out[$key] = __std_to_array($item);
        }

        return $out;
    }

    if (is_array($value)) {
        return array_map(fn ($item) => __std_to_array($item), $value);
    }

    return $value;
}

$path = __DIR__.'/openapi.json';

if (! file_exists($path)) {
    throw new RuntimeException("Missing OpenAPI export at {$path} — run scramble:export first.");
}

$doc = __std_to_array(json_decode(file_get_contents($path)));

$doc['info']['title'] = 'ABSA API Hub';

$doc['components']['schemas'] = array_replace(
    $doc['components']['schemas'] ?? [],
    StatementsOpenApiSchemas::componentSchemas()
);

foreach ($doc['paths'] as $pPath => $methods) {
    foreach ($methods as $method => $operation) {
        if (! is_array($operation)) {
            continue;
        }

        $ref = StatementsOpenApiSchemas::schemaRefForOperation($operation['operationId'] ?? '');

        if ($ref !== null) {
            $doc['paths'][$pPath][$method]['responses']['200']['content']['application/json']['schema'] = $ref;
        }
    }
}

file_put_contents($path, json_encode($doc, JSON_PRETTY_PRINT));

echo "OpenAPI document enriched: {$path}".PHP_EOL;
