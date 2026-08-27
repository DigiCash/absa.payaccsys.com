<?php

declare(strict_types=1);

use App\DTOs\StatementsAPI\Responses\Health\HealthResponseDTO;

/*
 * Round-trip assertions for the inline Health response ({ status }).
 * Hermetic: no DB / HTTP.
 */

it('round-trips a populated Health response', function () {
    $payload = ['status' => 'ok'];
    expect(HealthResponseDTO::fromArray($payload)->toArray())->toBe($payload);
});

it('casts the status string through fromArray', function () {
    $dto = HealthResponseDTO::fromArray(['status' => 'degraded']);
    expect($dto->status)->toBe('degraded');
    expect($dto->toArray())->toBe(['status' => 'degraded']);
});

it('drops the null status when absent', function () {
     expect(HealthResponseDTO::fromArray([])->toArray())->toBe([]);
     expect(HealthResponseDTO::fromArray([])->status)->toBeNull();
});