<?php

declare(strict_types=1);

namespace Tests\Feature\StatementsAPI;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\StatementsAPI\Support\FacadeTestSupport;
use Tests\TestCase;

/**
 * Feature tests for GET /api/v1/statements/health (facade Health operation).
 */
uses(TestCase::class);

beforeEach(function (): void {
    FacadeTestSupport::wire();
});

it('rejects an unauthenticated health request with 401', function (): void {
    $this->getJson('/api/v1/statements/health')
        ->assertStatus(401);
});

it('returns the upstream health envelope for an authenticated caller and forwards the resolved bearer token', function (): void {
    Http::fake([
        'https://statements.test/v1/health' => Http::response(['status' => 'OK'], 200),
    ]);

    Sanctum::actingAs(User::factory()->make());

    $this->getJson('/api/v1/statements/health')
        ->assertOk()
        ->assertExactJson(['status' => 'OK']);

    Http::assertSent(function (Request $request): bool {
        return $request->method() === 'GET'
            && $request->url() === 'https://statements.test/v1/health'
            && $request->hasHeader('Authorization', 'Bearer facade-test-token');
    });
});

it('maps an upstream failure onto the uniform error envelope', function (): void {
    Http::fake([
        'https://statements.test/v1/health' => Http::response(
            ['Code' => 'UPSTREAM_DOWN', 'Message' => 'upstream unavailable'],
            502,
        ),
    ]);

    Sanctum::actingAs(User::factory()->make());

    $this->getJson('/api/v1/statements/health')
        ->assertStatus(502)
        ->assertJson([
            'error' => ['Code' => 'UPSTREAM_DOWN', 'Message' => 'upstream unavailable'],
        ]);
});

it('normalises a connection failure (status 0) to 502 with a fallback envelope', function (): void {
    Http::fake([
        'https://statements.test/v1/health' => fn () => throw new ConnectionException('boom'),
    ]);

    Sanctum::actingAs(User::factory()->make());

    $this->getJson('/api/v1/statements/health')
        ->assertStatus(502)
        ->assertJson([
            'error' => ['Code' => 'STATEMENTS_API_ERROR', 'Message' => 'Statements API error 0'],
        ]);
});
