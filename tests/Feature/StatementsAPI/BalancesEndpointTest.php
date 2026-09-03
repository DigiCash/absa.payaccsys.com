<?php

declare(strict_types=1);

namespace Tests\Feature\StatementsAPI;

use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\StatementsAPI\Support\FacadeTestSupport;
use Tests\TestCase;

/**
 * Feature tests for the facade Balances operations.
 *
 * GET /api/v1/statements/balances
 * GET /api/v1/statements/accounts/{accountId}/balances
 */
uses(TestCase::class);

beforeEach(function (): void {
    FacadeTestSupport::wire();
});

it('rejects unauthenticated balance requests with 401', function (): void {
    $this->getJson('/api/v1/statements/balances')->assertStatus(401);
    $this->getJson('/api/v1/statements/accounts/acc-123/balances')->assertStatus(401);
});

it('returns the upstream balances envelope for all accounts', function (): void {
    Http::fake([
        'https://statements.test/v1/balances' => Http::response(
            ['Data' => ['Balance' => [['AccountId' => 'acc-123', 'Type' => 'CLAV']]]],
            200,
        ),
    ]);

    Sanctum::actingAs(User::factory()->make());

    $this->getJson('/api/v1/statements/balances')
        ->assertOk()
        ->assertJson([
            'Data' => [
                'Balance' => [
                    ['AccountId' => 'acc-123', 'Type' => 'CLAV'],
                ],
            ],
        ]);

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://statements.test/v1/balances');
});

it('returns account balances and forwards the ABSA cross-cutting headers', function (): void {
    Http::fake([
        'https://statements.test/v1/accounts/acc-123/balances' => Http::response(
            ['Data' => ['Balance' => [['AccountId' => 'acc-123']]]],
            200,
        ),
    ]);

    Sanctum::actingAs(User::factory()->make());

    $this->getJson('/api/v1/statements/accounts/acc-123/balances', [
        'X-Absa-ClientInteractionId' => 'interaction-1',
        'X-Absa-Initiating-UserId' => 'user-7',
        'X-Absa-Nonce' => 'nonce-1',
    ])
        ->assertOk()
        ->assertJson(['Data' => ['Balance' => [['AccountId' => 'acc-123']]]]);

    Http::assertSent(function (Request $request): bool {
        return $request->url() === 'https://statements.test/v1/accounts/acc-123/balances'
            && $request->hasHeader('X-Absa-ClientInteractionId', 'interaction-1')
            && $request->hasHeader('X-Absa-Initiating-UserId', 'user-7')
            && $request->hasHeader('X-Absa-Nonce', 'nonce-1')
            && $request->hasHeader('Authorization', 'Bearer facade-test-token');
    });
});

it('maps an upstream failure on account balances onto the error envelope', function (): void {
    Http::fake([
        'https://statements.test/v1/accounts/acc-123/balances' => Http::response(
            ['Code' => 'RESOURCE_NOT_FOUND', 'Message' => 'no such account'],
            404,
        ),
    ]);

    Sanctum::actingAs(User::factory()->make());

    $this->getJson('/api/v1/statements/accounts/acc-123/balances')
        ->assertStatus(404)
        ->assertJson([
            'error' => ['Code' => 'RESOURCE_NOT_FOUND', 'Message' => 'no such account'],
        ]);
});
