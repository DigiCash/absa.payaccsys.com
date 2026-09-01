<?php

declare(strict_types=1);

namespace Tests\Unit\Services\StatementsAPI;

use App\DTOs\StatementsAPI\Errors\ErrorResponseDTO;
use App\DTOs\StatementsAPI\Requests\AbsaRequestHeaders;
use App\DTOs\StatementsAPI\Requests\GetAccountBalancesRequestDTO;
use App\DTOs\StatementsAPI\Requests\GetAllStatementsRequestDTO;
use App\DTOs\StatementsAPI\Requests\GetBalancesRequestDTO;
use App\DTOs\StatementsAPI\Requests\GetHealthRequestDTO;
use App\DTOs\StatementsAPI\Requests\GetIntraDayStatementRequestDTO;
use App\DTOs\StatementsAPI\Requests\GetStatementRequestDTO;
use App\DTOs\StatementsAPI\Requests\GetStatementTransactionsRequestDTO;
use App\DTOs\StatementsAPI\Requests\GetStatementsRequestDTO;
use App\DTOs\StatementsAPI\Requests\Query\PaginationQuery;
use App\DTOs\StatementsAPI\Requests\Query\StatementDateRangeQuery;
use App\DTOs\StatementsAPI\Responses\Balances\BalancesReadResponseDTO;
use App\DTOs\StatementsAPI\Responses\Health\HealthResponseDTO;
use App\DTOs\StatementsAPI\Responses\Statements\StatementReadResponseDTO;
use App\DTOs\StatementsAPI\Responses\StatementTransactions\TransactionReadResponseDTO;
use App\DTOs\StatementsAPI\Transport\StatementsApiClient;
use App\DTOs\StatementsAPI\Transport\StatementsApiClientConfig;
use App\DTOs\StatementsAPI\Transport\StatementsApiException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/*
 * Happy-path transport tests for `StatementsApiClient` (M3).
 *
 * Each operation is driven through `Http::fake()`, so there is no real
 * network and no DB. We assert the return type is the matching response DTO
 * and that the outbound GET carried the correct path params, query string and
 * ABSA headers (incl. the config-supplied `Authorization: Bearer {apiKey}`).
 */
uses(TestCase::class);

const BASE_URL = 'https://api.example.test/statements/v1';
const API_KEY = 'sk_test_statements';

/**
 * Build an immutable config via the explicit-array override so the test never
 * touches `config('absa.statements')` or `storage_path()`.
 */
function statementsConfig(): StatementsApiClientConfig
{
    return StatementsApiClientConfig::fromConfig([
        'base_url'      => BASE_URL,
        'api_key'       => API_KEY,
        'cert_path'     => '/tmp/certs/client.pem',
        'key_path'      => '/tmp/certs/client.key',
        'passphrase' => 's3cr3t',
       ]);
}

/**
 * The cross-cutting ABSA headers attached to every operation.
 */
function absaHeaders(): AbsaRequestHeaders
{
    return new AbsaRequestHeaders(
        clientInteractionId: 'CI-001',
        initiatingUserId: 'user@example.co.za',
        initiatingCompanyProfileId: 'COP-001',
        nonce: 'NONCE-001',
       );
}

/**
 * Assert the request carried the config bearer plus the four ABSA headers.
 * Returns a bool so it can be composed with path/query checks inside
 * `Http::assertSent()`.
 */
function assertAbsaHeaders(Request $request): bool
{
    expect($request->hasHeader('Authorization'))->toBeTrue();
    expect($request->header('Authorization'))->toContain('Bearer ' . API_KEY);
    expect($request->hasHeader('X-Absa-ClientInteractionId'))->toBeTrue();
    expect($request->hasHeader('X-Absa-Initiating-UserId'))->toBeTrue();
    expect($request->hasHeader('X-Absa-Initiating-CompanyProfileId'))->toBeTrue();
    expect($request->hasHeader('X-Absa-Nonce'))->toBeTrue();

    return true;
}

/**
 * Build a canonical ABSA `ErrorResponse` JSON body — the single shared error
 * payload used for every 4xx/5xx status (400/401/403/404/429/500). `Code`
 * and `Message` are always present; `Id`/`Errors` are optional. Mirrors the
 * shape asserted in `ErrorResponseTest`.
 */
function errorBody(string $code, string $message, ?string $id = null, ?array $errors = null): array
{
    $body = ['Code' => $code, 'Message' => $message];
    if ($id !== null) {
        $body['Id'] = $id;
     }
    if ($errors !== null) {
        $body['Errors'] = $errors;
     }
    return $body;
}

/**
 * Run a client operation that is expected to fail, capturing the thrown
 * `StatementsApiException` so the test can assert on `->status` and the decoded
 * `->error` `ErrorResponseDTO`. With `retryAttempts = 0` (the default in
 * `statementsConfig()`), the failure is reported on the first call, so this is
 * deterministic for every status — including the retriable 429/500.
 */
function expectStatementsException(
    callable $operation,
    int $expectedStatus,
    ?string $code = null,
    ?string $message = null,
): StatementsApiException {
    try {
        $operation();
     } catch (StatementsApiException $e) {
        expect($e->status)->toBe($expectedStatus);

        if ($code !== null || $message !== null) {
            expect($e->error)->toBeInstanceOf(ErrorResponseDTO::class);
            expect($e->error?->Code)->toBe($code);
            expect($e->error?->Message)->toBe($message);
         }

        return $e;
     }

    throw new \RuntimeException("Expected StatementsApiException (status {$expectedStatus}) was not thrown");
}

// ---------------------------------------------------------------------------
// 1. getHealth → GET /health → HealthResponseDTO
// ---------------------------------------------------------------------------
it('getHealth returns a HealthResponseDTO from GET /health', function () {
    Http::fake(fn (Request $request) => Http::response(['status' => 'OK'], 200));

    $result = new StatementsApiClient(statementsConfig())
         ->getHealth(new GetHealthRequestDTO(absaHeaders()));

    expect($result)->toBeInstanceOf(HealthResponseDTO::class);
    expect($result->status)->toBe('OK');

    Http::assertSent(function (Request $request) {
        return $request->method() === 'GET'
             && str_contains($request->url(), '/health')
             && assertAbsaHeaders($request);
     });
});

// ---------------------------------------------------------------------------
// 2. getBalances → GET /balances → BalancesReadResponseDTO
// ---------------------------------------------------------------------------
it('getBalances returns a BalancesReadResponseDTO from GET /balances', function () {
    Http::fake(fn (Request $request) => Http::response([
          'Data' => ['Balance' => [
              ['AccountId' => '1234567890', 'Type' => 'EXTERNAL'],
          ]],
      ], 200));

    $result = new StatementsApiClient(statementsConfig())
         ->getBalances(new GetBalancesRequestDTO(absaHeaders()));

    expect($result)->toBeInstanceOf(BalancesReadResponseDTO::class);
    expect($result->Data->Balance[0]->AccountId)->toBe('1234567890');

    Http::assertSent(function (Request $request) {
        return $request->method() === 'GET'
             && str_contains($request->url(), '/balances')
             && assertAbsaHeaders($request);
     });
});

// ---------------------------------------------------------------------------
// 3. getAccountBalances → GET /accounts/{accountId}/balances
// ---------------------------------------------------------------------------
it('getAccountBalances interpolates the accountId path param', function () {
    Http::fake(fn (Request $request) => Http::response([
           'Data' => ['Balance' => [
               ['AccountId' => 'ACC-1', 'Type' => 'EXTERNAL'],
           ]],
       ], 200));

    $result = new StatementsApiClient(statementsConfig())
          ->getAccountBalances(new GetAccountBalancesRequestDTO('ACC-1', absaHeaders()));

    expect($result)->toBeInstanceOf(BalancesReadResponseDTO::class);
    expect($result->Data->Balance[0]->AccountId)->toBe('ACC-1');

    Http::assertSent(function (Request $request) {
        return $request->method() === 'GET'
             && str_contains($request->url(), '/accounts/ACC-1/balances')
             && assertAbsaHeaders($request);
      });
});

// ---------------------------------------------------------------------------
// 4. getStatements → GET /accounts/{accountId}/statements?fromStatementDateTime=...
// ---------------------------------------------------------------------------
it('getStatements appends the date-range query string', function () {
    Http::fake(fn (Request $request) => Http::response([
           'Data' => ['Statement' => [
               ['AccountId' => 'ACC-1', 'StatementId' => 'STMT-1'],
           ]],
       ], 200));

    $result = new StatementsApiClient(statementsConfig())
          ->getStatements(new GetStatementsRequestDTO(
                accountId: 'ACC-1',
                dateRange: new StatementDateRangeQuery(
                    fromStatementDateTime: '2025-01-01',
                    toStatementDateTime: '2025-01-31',
                ),
                headers: absaHeaders(),
            ));

    expect($result)->toBeInstanceOf(StatementReadResponseDTO::class);
    expect($result->Data->Statement[0]->StatementId)->toBe('STMT-1');

    Http::assertSent(function (Request $request) {
        return $request->method() === 'GET'
              && str_contains($request->url(), '/accounts/ACC-1/statements')
              && str_contains($request->url(), 'fromStatementDateTime=2025-01-01')
              && str_contains($request->url(), 'toStatementDateTime=2025-01-31')
              && assertAbsaHeaders($request);
      });
});

// ---------------------------------------------------------------------------
// 5. getStatement → GET /accounts/{accountId}/statements/{statementId}
// ---------------------------------------------------------------------------
it('getStatement interpolates both accountId and statementId path params', function () {
    Http::fake(fn (Request $request) => Http::response([
           'Data' => ['Statement' => [
               ['AccountId' => 'ACC-1', 'StatementId' => 'STMT-42'],
           ]],
       ], 200));

    $result = new StatementsApiClient(statementsConfig())
          ->getStatement(new GetStatementRequestDTO('ACC-1', 'STMT-42', absaHeaders()));

    expect($result)->toBeInstanceOf(StatementReadResponseDTO::class);
    expect($result->Data->Statement[0]->StatementId)->toBe('STMT-42');

    Http::assertSent(function (Request $request) {
        return $request->method() === 'GET'
             && str_contains($request->url(), '/accounts/ACC-1/statements/STMT-42')
             && assertAbsaHeaders($request);
      });
});

// ---------------------------------------------------------------------------
// 6. getStatementTransactions → .../statements/{statementId}/transactions
// ---------------------------------------------------------------------------
it('getStatementTransactions interpolates path params and appends pagination', function () {
    Http::fake(fn (Request $request) => Http::response([
            'Data' => ['Transaction' => [
                ['AccountId' => 'ACC-1', 'TransactionId' => 'TX-1'],
            ]],
        ], 200));

    $result = new StatementsApiClient(statementsConfig())
           ->getStatementTransactions(new GetStatementTransactionsRequestDTO(
                accountId: 'ACC-1',
                statementId: 'STMT-42',
                pagination: new PaginationQuery(pg: 2, pgSize: 50),
                headers: absaHeaders(),
             ));

    expect($result)->toBeInstanceOf(TransactionReadResponseDTO::class);
    expect($result->Data->Transaction[0]->TransactionId)->toBe('TX-1');

    Http::assertSent(function (Request $request) {
        return $request->method() === 'GET'
                && str_contains($request->url(), '/accounts/ACC-1/statements/STMT-42/transactions')
                && str_contains($request->url(), 'pg=2')
                && str_contains($request->url(), 'pgSize=50')
                && assertAbsaHeaders($request);
        });
});

// ---------------------------------------------------------------------------
// 7. getAllStatements → GET /statements
// ---------------------------------------------------------------------------
it('getAllStatements hits the top-level /statements path', function () {
    Http::fake(fn (Request $request) => Http::response([
            'Data' => ['Statement' => [
                ['StatementId' => 'STMT-ALL'],
            ]],
        ], 200));

    $result = new StatementsApiClient(statementsConfig())
           ->getAllStatements(new GetAllStatementsRequestDTO(headers: absaHeaders()));

    expect($result)->toBeInstanceOf(StatementReadResponseDTO::class);
    expect($result->Data->Statement[0]->StatementId)->toBe('STMT-ALL');

    Http::assertSent(function (Request $request) {
        return $request->method() === 'GET'
                && str_contains($request->url(), '/statements')
                && assertAbsaHeaders($request);
        });
});

// ---------------------------------------------------------------------------
// 8. getIntraDayStatement → GET /accounts/{accountId}/intraday-statement
// ---------------------------------------------------------------------------
it('getIntraDayStatement interpolates the accountId and appends pagination', function () {
    Http::fake(fn (Request $request) => Http::response([
            'Data' => ['Transaction' => [
                ['AccountId' => 'ACC-1', 'TransactionId' => 'TX-ID'],
            ]],
        ], 200));

    $result = new StatementsApiClient(statementsConfig())
           ->getIntraDayStatement(new GetIntraDayStatementRequestDTO(
                accountId: 'ACC-1',
                pagination: new PaginationQuery(index: 5),
                headers: absaHeaders(),
             ));

    expect($result)->toBeInstanceOf(TransactionReadResponseDTO::class);
    expect($result->Data->Transaction[0]->TransactionId)->toBe('TX-ID');

    Http::assertSent(function (Request $request) {
        return $request->method() === 'GET'
                && str_contains($request->url(), '/accounts/ACC-1/intraday-statement')
                && str_contains($request->url(), 'index=5')
                && assertAbsaHeaders($request);
        });
});

// ---------------------------------------------------------------------------
// M4: error handling - every non-2xx maps to a StatementsApiException
// ---------------------------------------------------------------------------

it('maps a 400 Bad Request to a StatementsApiException with the decoded error', function () {
    Http::fake(fn (Request $request) => Http::response(errorBody('BAD_REQUEST', 'Invalid request'), 400));

    expectStatementsException(
        fn () => (new StatementsApiClient(statementsConfig()))->getHealth(new GetHealthRequestDTO(absaHeaders())),
         400,
        code: 'BAD_REQUEST',
        message: 'Invalid request',
       );
});

it('maps a 401 Unauthorized to a StatementsApiException with the decoded error', function () {
    Http::fake(fn (Request $request) => Http::response(errorBody('UNAUTHORIZED', 'Missing API key'), 401));

    expectStatementsException(
        fn () => (new StatementsApiClient(statementsConfig()))->getHealth(new GetHealthRequestDTO(absaHeaders())),
         401,
        code: 'UNAUTHORIZED',
        message: 'Missing API key',
       );
});

it('maps a 403 Forbidden to a StatementsApiException with the decoded error', function () {
    Http::fake(fn (Request $request) => Http::response(errorBody('FORBIDDEN', 'Access denied'), 403));

    expectStatementsException(
        fn () => (new StatementsApiClient(statementsConfig()))->getHealth(new GetHealthRequestDTO(absaHeaders())),
         403,
        code: 'FORBIDDEN',
        message: 'Access denied',
       );
});

it('maps a 404 Not Found to a StatementsApiException with the decoded error', function () {
    Http::fake(fn (Request $request) => Http::response(errorBody('NOT_FOUND', 'Resource not found'), 404));

    expectStatementsException(
        fn () => (new StatementsApiClient(statementsConfig()))->getHealth(new GetHealthRequestDTO(absaHeaders())),
         404,
        code: 'NOT_FOUND',
        message: 'Resource not found',
       );
});

it('maps a 429 Too Many Requests (retriable) to a StatementsApiException on the first call', function () {
    Http::fake(fn (Request $request) => Http::response(errorBody('RATE_LIMITED', 'Too many requests'), 429));

    expectStatementsException(
        fn () => (new StatementsApiClient(statementsConfig()))->getHealth(new GetHealthRequestDTO(absaHeaders())),
         429,
        code: 'RATE_LIMITED',
        message: 'Too many requests',
       );
});

it('maps a 500 Internal Server Error (retriable) to a StatementsApiException on the first call', function () {
    Http::fake(fn (Request $request) => Http::response(errorBody('INTERNAL_ERROR', 'Server error'), 500));

    expectStatementsException(
        fn () => (new StatementsApiClient(statementsConfig()))->getHealth(new GetHealthRequestDTO(absaHeaders())),
         500,
        code: 'INTERNAL_ERROR',
        message: 'Server error',
       );
});

it('decodes nested error details onto the ErrorResponseDTO', function () {
    Http::fake(fn (Request $request) => Http::response(errorBody(
        code: 'VALIDATION_ERROR',
        message: 'Invalid request body',
        id: 'abc-123',
        errors: [
             [
                  'ErrorCode' => 'INVALID_PARAMETER',
                  'Message' => 'Field "accountId" is required',
                  'Path' => '$.accountId',
                  'Url' => 'https://api.absa.co.za/errors/INVALID_PARAMETER',
              ],
          ],
      ), 400));

     $exception = expectStatementsException(
        fn () => (new StatementsApiClient(statementsConfig()))->getHealth(new GetHealthRequestDTO(absaHeaders())),
         400,
        code: 'VALIDATION_ERROR',
        message: 'Invalid request body',
       );

    expect($exception->error->Id)->toBe('abc-123');
    expect($exception->error->Errors[0]->ErrorCode)->toBe('INVALID_PARAMETER');
    expect($exception->error->Errors[0]->Path)->toBe('$.accountId');
    expect($exception->error->Errors[0]->Url)->toBe('https://api.absa.co.za/errors/INVALID_PARAMETER');
});

it('leaves the error body null when a 500 response is not JSON', function () {
    Http::fake(fn (Request $request) => Http::response('Internal Server Error', 500));

     $exception = expectStatementsException(
        fn () => (new StatementsApiClient(statementsConfig()))->getHealth(new GetHealthRequestDTO(absaHeaders())),
         500,
       );

    expect($exception->error)->toBeNull();
});
