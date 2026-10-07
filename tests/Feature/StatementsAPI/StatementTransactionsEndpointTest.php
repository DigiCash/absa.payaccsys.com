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
 * Feature tests for the facade Statement Transactions operations.
 *
 * GET /api/v1/statements/accounts/{accountId}/statements/{statementId}/transactions
 * GET /api/v1/statements/accounts/{accountId}/intraday-statement
 */
uses(TestCase::class);

beforeEach(function (): void {
    FacadeTestSupport::wire();
});

it('rejects unauthenticated transaction requests with 401', function (): void {
    $this->getJson('/api/v1/statements/accounts/acc-123/statements/stmt-9/transactions')->assertStatus(401);
    $this->getJson('/api/v1/statements/accounts/acc-123/intraday-statement')->assertStatus(401);
});

it('returns the transactions for a statement and forwards the pagination query', function (): void {
    Http::fake([
        'https://statements.test/v1/accounts/acc-123/statements/stmt-9/transactions*' => Http::response(
            ['Data' => ['Transaction' => [['AccountId' => 'acc-123', 'TransactionId' => 'txn-1']]]],
            200,
        ),
    ]);

    Sanctum::actingAs(User::factory()->make());

    $this->getJson('/api/v1/statements/accounts/acc-123/statements/stmt-9/transactions?pg=1&pgSize=25')
        ->assertOk()
        ->assertJson([
            'Data' => [
                'Transaction' => [
                    ['AccountId' => 'acc-123', 'TransactionId' => 'txn-1'],
                ],
            ],
        ]);

    Http::assertSent(function (Request $request): bool {
        return str_starts_with($request->url(), 'https://statements.test/v1/accounts/acc-123/statements/stmt-9/transactions')
            && $request['pg'] === 1
            && $request['pgSize'] === 25
            && $request->hasHeader('Authorization', 'Bearer facade-test-token');
    });
});

it('returns the intraday statement and forwards the cursor (index) query', function (): void {
    Http::fake([
        'https://statements.test/v1/accounts/acc-123/intraday-statement*' => Http::response(
            ['Data' => ['Transaction' => [['AccountId' => 'acc-123', 'TransactionId' => 'txn-idx-5']]]],
            200,
        ),
    ]);

    Sanctum::actingAs(User::factory()->make());

    $this->getJson('/api/v1/statements/accounts/acc-123/intraday-statement?index=5&pgSize=10')
        ->assertOk()
        ->assertJson([
            'Data' => [
                'Transaction' => [
                    ['AccountId' => 'acc-123', 'TransactionId' => 'txn-idx-5'],
                ],
            ],
        ]);

    Http::assertSent(function (Request $request): bool {
        return str_starts_with($request->url(), 'https://statements.test/v1/accounts/acc-123/intraday-statement')
            && $request['index'] === 5
            && $request['pgSize'] === 10;
    });
});

it('rejects invalid intraday cursor values with 422', function (): void {
    Sanctum::actingAs(User::factory()->make());

    $this->getJson('/api/v1/statements/accounts/acc-123/intraday-statement?index=-1')
        ->assertStatus(422)
        ->assertJsonValidationErrors(['index']);

    $this->getJson('/api/v1/statements/accounts/acc-123/intraday-statement?time=99%3A99%3A99')
        ->assertStatus(422)
        ->assertJsonValidationErrors(['time']);

    $this->getJson('/api/v1/statements/accounts/acc-123/statements/stmt-9/transactions?pg=0')
        ->assertStatus(422)
        ->assertJsonValidationErrors(['pg']);
});

it('maps an upstream failure on transactions onto the error envelope', function (): void {
    Http::fake([
        'https://statements.test/v1/accounts/acc-123/statements/stmt-9/transactions' => Http::response(
            ['Code' => 'RESOURCE_NOT_FOUND', 'Message' => 'statement has no transactions'],
            404,
        ),
    ]);

    Sanctum::actingAs(User::factory()->make());

    $this->getJson('/api/v1/statements/accounts/acc-123/statements/stmt-9/transactions')
        ->assertStatus(404)
        ->assertJson([
            'error' => ['Code' => 'RESOURCE_NOT_FOUND', 'Message' => 'statement has no transactions'],
        ]);
});
