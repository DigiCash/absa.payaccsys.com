<?php

declare(strict_types=1);

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

/*
 * Request-DTO hydration. Hermetic: no DB / HTTP.
 *
 * Request DTOs expose path()/query()/headers() so the HTTP client can build a
 * call from them; toArray() composes those three buckets.
 */

it('hydrates AbsaRequestHeaders and emits exact ABSA header names, dropping nulls', function () {
    $dto = AbsaRequestHeaders::fromArray([
           'authorization' => 'Bearer token',
           'clientInteractionId' => 'cid-1',
           'initiatingUserId' => 'user-1',
           'initiatingCompanyProfileId' => 'cp-1',
           'nonce' => 'nonce-1',
       ]);

    expect($dto->toArray())->toBe([
           'Authorization' => 'Bearer token',
           'X-Absa-ClientInteractionId' => 'cid-1',
           'X-Absa-Initiating-UserId' => 'user-1',
           'X-Absa-Initiating-CompanyProfileId' => 'cp-1',
           'X-Absa-Nonce' => 'nonce-1',
       ]);
});

it('drops the unset ABSA headers when not supplied', function () {
     expect(AbsaRequestHeaders::fromArray(['nonce' => 'n'])->toArray())
          ->toBe(['X-Absa-Nonce' => 'n']);
     expect(AbsaRequestHeaders::fromArray([])->toArray())->toBe([]);
});

it('hydrates PaginationQuery casting index/pg/pgSize to int and dropping nulls', function () {
    $dto = PaginationQuery::fromArray([
           'index' => '3',
           'time' => 't-abc',
           'pg' => '2',
           'pgSize' => '50',
       ]);

    expect($dto->index)->toBeInt()->toBe(3);
    expect($dto->pg)->toBeInt()->toBe(2);
    expect($dto->pgSize)->toBeInt()->toBe(50);
    expect($dto->time)->toBe('t-abc');
    expect($dto->toArray())->toBe([
           'index' => 3,
           'time' => 't-abc',
           'pg' => 2,
           'pgSize' => 50,
       ]);
});

it('drops null pagination fields when absent', function () {
     expect(PaginationQuery::fromArray([])->toArray())->toBe([]);
     expect(PaginationQuery::fromArray(['pg' => 1])->toArray())->toBe(['pg' => 1]);
});

it('hydrates StatementDateRangeQuery and drops the null bound', function () {
    $dto = StatementDateRangeQuery::fromArray([
           'fromStatementDateTime' => '2025-09-01T00:00:00Z',
           'toStatementDateTime' => '2025-09-30T23:59:59Z',
       ]);

    expect($dto->toArray())->toBe([
           'fromStatementDateTime' => '2025-09-01T00:00:00Z',
           'toStatementDateTime' => '2025-09-30T23:59:59Z',
       ]);
     expect(StatementDateRangeQuery::fromArray([])->toArray())->toBe([]);
});

it('hydrates a no-parameter request (GetBalances / GetHealth) with headers only', function () {
    $balanced = GetBalancesRequestDTO::fromArray([
           'headers' => ['nonce' => 'n'],
       ]);
    expect($balanced->toArray())->toBe([
           'query' => [],
           'headers' => ['X-Absa-Nonce' => 'n'],
       ]);
    expect($balanced->query())->toBe([]);

     expect(GetHealthRequestDTO::fromArray([])->toArray())->toBe([
           'query' => [],
           'headers' => [],
       ]);
});

it('hydrates a two-segment path (GetStatement / GetStatementTransactions)', function () {
    $statement = GetStatementRequestDTO::fromArray([
           'accountId' => '1',
           'statementId' => 'S-100',
       ]);
    expect($statement->path())->toBe(['accountId' => '1', 'statementId' => 'S-100']);
    expect($statement->query())->toBe([]);

      $transactions = GetStatementTransactionsRequestDTO::fromArray([
           'accountId' => '1',
           'statementId' => 'S-100',
           'pagination' => ['pg' => 2, 'pgSize' => 25],
       ]);
    expect($transactions->path())->toBe(['accountId' => '1', 'statementId' => 'S-100']);
    expect($transactions->query())->toBe(['pg' => 2, 'pgSize' => 25]);
});

it('merges the date-range and pagination query buckets (GetStatements)', function () {
    $dto = GetStatementsRequestDTO::fromArray([
           'accountId' => '1',
           'dateRange' => [
               'fromStatementDateTime' => '2025-09-01T00:00:00Z',
               'toStatementDateTime' => '2025-09-30T23:59:59Z',
           ],
           'pagination' => ['pg' => 1, 'pgSize' => 100],
       ]);

    expect($dto->path())->toBe(['accountId' => '1']);
    expect($dto->query())->toBe([
           'fromStatementDateTime' => '2025-09-01T00:00:00Z',
           'toStatementDateTime' => '2025-09-30T23:59:59Z',
           'pg' => 1,
           'pgSize' => 100,
       ]);
    expect(array_keys($dto->toArray()))->toBe(['path', 'query', 'headers']);
});

it('hydrates the SA-only GetAllStatements and IntraDayStatement requests', function () {
    $all = GetAllStatementsRequestDTO::fromArray([
           'dateRange' => [
               'fromStatementDateTime' => '2025-09-01T00:00:00Z',
           ],
           'pagination' => ['index' => 4],
       ]);
    expect($all->query())->toBe([
           'fromStatementDateTime' => '2025-09-01T00:00:00Z',
           'index' => 4,
       ]);
    expect($all->toArray())->toHaveKeys(['query', 'headers']);

      $intraday = GetIntraDayStatementRequestDTO::fromArray([
           'accountId' => '1',
           'pagination' => ['index' => 7, 'time' => 't-1'],
       ]);
    expect($intraday->path())->toBe(['accountId' => '1']);
    expect($intraday->query())->toBe(['index' => 7, 'time' => 't-1']);
});
it('casts the GetAccountBalances accountId path segment to a string', function () {
    $dto = GetAccountBalancesRequestDTO::fromArray([
           'accountId' => 12345678,
           'headers' => ['nonce' => 'n'],
       ]);

    expect($dto->accountId)->toBe('12345678');
    expect($dto->path())->toBe(['accountId' => '12345678']);
    expect($dto->query())->toBe([]);
    expect($dto->toArray())->toBe([
           'path' => ['accountId' => '12345678'],
           'query' => [],
           'headers' => ['X-Absa-Nonce' => 'n'],
       ]);
});