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
 * Feature tests for the facade Statement operations.
 *
 * GET /api/v1/statements
 * GET /api/v1/statements/accounts/{accountId}/statements
 * GET /api/v1/statements/accounts/{accountId}/statements/{statementId}
 */
uses(TestCase::class);

beforeEach(function (): void {
    FacadeTestSupport::wire();
});

it('rejects unauthenticated statement requests with 401', function (): void {
    $this->getJson('/api/v1/statements')->assertStatus(401);
    $this->getJson('/api/v1/statements/accounts/acc-123/statements')->assertStatus(401);
    $this->getJson('/api/v1/statements/accounts/acc-123/statements/stmt-eod-1')->assertStatus(401);
});

it('returns all EOD statements (SA-only) for an authenticated caller', function (): void {
    Http::fake([
        'https://statements.test/v1/statements' => Http::response(
            ['Data' => ['Statement' => [['AccountId' => 'acc-123', 'StatementId' => 'stmt-eod-1']]]],
            200,
        ),
    ]);

    Sanctum::actingAs(User::factory()->make());

    $this->getJson('/api/v1/statements')
        ->assertOk()
        ->assertJson([
            'Data' => [
                'Statement' => [
                    ['AccountId' => 'acc-123', 'StatementId' => 'stmt-eod-1'],
                ],
            ],
        ])
        ->assertJsonStructure(['Data' => ['Statement' => [['AccountId', 'StatementId']]]]);

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://statements.test/v1/statements');
});

it('lists statements for an account and forwards the date-range and pagination query', function (): void {
    Http::fake([
        'https://statements.test/v1/accounts/acc-123/statements*' => Http::response(
            ['Data' => ['Statement' => [['AccountId' => 'acc-123', 'StatementId' => 'stmt-eod-1']]]],
            200,
        ),
    ]);

    Sanctum::actingAs(User::factory()->make());

    $this->getJson(
        '/api/v1/statements/accounts/acc-123/statements?'.
        'fromStatementDateTime=2026-01-01T00%3A00%3A00Z&toStatementDateTime=2026-01-31T23%3A59%3A59Z&pg=2&pgSize=50',
    )
        ->assertOk();

    Http::assertSent(function (Request $request): bool {
        return str_starts_with($request->url(), 'https://statements.test/v1/accounts/acc-123/statements')
            && $request['fromStatementDateTime'] === '2026-01-01T00:00:00Z'
            && $request['toStatementDateTime'] === '2026-01-31T23:59:59Z'
            && $request['pg'] === 2
            && $request['pgSize'] === 50;
    });
});

it('returns a single statement for an account', function (): void {
    Http::fake([
        'https://statements.test/v1/accounts/acc-123/statements/stmt-eod-1' => Http::response(
            ['Data' => ['Statement' => [['AccountId' => 'acc-123', 'StatementId' => 'stmt-eod-1']]]],
            200,
        ),
    ]);

    Sanctum::actingAs(User::factory()->make());

    $this->getJson('/api/v1/statements/accounts/acc-123/statements/stmt-eod-1')
        ->assertOk()
        ->assertJson(['Data' => ['Statement' => [['StatementId' => 'stmt-eod-1']]]]);

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://statements.test/v1/accounts/acc-123/statements/stmt-eod-1');
});

it('rejects invalid pagination and inverted date ranges with 422', function (): void {
    Sanctum::actingAs(User::factory()->make());

    $this->getJson('/api/v1/statements/accounts/acc-123/statements?pg=0&pgSize=-5')
        ->assertStatus(422)
        ->assertJsonValidationErrors(['pg', 'pgSize']);

    $this->getJson('/api/v1/statements/accounts/acc-123/statements?pgSize=abc')
        ->assertStatus(422)
        ->assertJsonValidationErrors(['pgSize']);

    $this->getJson(
        '/api/v1/statements?'.
        'fromStatementDateTime=2026-02-01T00%3A00%3A00Z&toStatementDateTime=2026-01-01T00%3A00%3A00Z',
    )
        ->assertStatus(422)
        ->assertJsonValidationErrors(['toStatementDateTime']);
});

it('maps an upstream failure on account statements onto the error envelope', function (): void {
    Http::fake([
        'https://statements.test/v1/accounts/acc-123/statements' => Http::response(
            ['Code' => 'UPSTREAM_DOWN', 'Message' => 'statements unavailable'],
            503,
        ),
    ]);

    Sanctum::actingAs(User::factory()->make());

    $this->getJson('/api/v1/statements/accounts/acc-123/statements')
        ->assertStatus(503)
        ->assertJson([
            'error' => ['Code' => 'UPSTREAM_DOWN', 'Message' => 'statements unavailable'],
        ]);
});
