<?php

declare(strict_types=1);

namespace Tests\Unit\DTOs\StatementsAPI\Support;

use App\DTOs\StatementsAPI\Requests\GetAccountBalancesRequestDTO;
use App\DTOs\StatementsAPI\Requests\GetAllStatementsRequestDTO;
use App\DTOs\StatementsAPI\Requests\GetBalancesRequestDTO;
use App\DTOs\StatementsAPI\Requests\GetHealthRequestDTO;
use App\DTOs\StatementsAPI\Requests\GetIntraDayStatementRequestDTO;
use App\DTOs\StatementsAPI\Requests\GetStatementRequestDTO;
use App\DTOs\StatementsAPI\Requests\GetStatementsRequestDTO;
use App\DTOs\StatementsAPI\Requests\GetStatementTransactionsRequestDTO;
use App\DTOs\StatementsAPI\Responses\Balances\BalancesReadResponseDTO;
use App\DTOs\StatementsAPI\Responses\Health\HealthResponseDTO;
use App\DTOs\StatementsAPI\Responses\Statements\StatementReadResponseDTO;
use App\DTOs\StatementsAPI\Responses\StatementTransactions\TransactionReadResponseDTO;
use App\DTOs\StatementsAPI\Support\StatementsApiClientInterface;

/**
 * Hermetic, in-memory test double for {@see StatementsApiClientInterface}.
 *
 * Zero DB / network: every operation is scripted from the `$responses` map
 * passed to the constructor (method name => response DTO to return, or a
 * `\Throwable` to throw). Call history is recorded on {@see self::$called}
 * (operation names) and {@see self::$received} (the request DTOs) so tests can
 * assert the exact calls a collaborator made.
 */
final class FakeStatementsApiClient implements StatementsApiClientInterface
{
    /**
     * @var array<string, object|\Throwable> method name => response DTO | throwable
     */
    private const array SCRIPTED = [];

    /** @var list<string> */
    public array $called = [];

    /** @var list<object> */
    public array $received = [];

    /**
     * @param  array<string, object|\Throwable>  $responses
     */
    public function __construct(
        private array $responses = self::SCRIPTED,
    ) {}

    public function getHealth(GetHealthRequestDTO $request): HealthResponseDTO
    {
        return $this->dispatch(__FUNCTION__, $request);
    }

    public function getBalances(GetBalancesRequestDTO $request): BalancesReadResponseDTO
    {
        return $this->dispatch(__FUNCTION__, $request);
    }

    public function getAccountBalances(GetAccountBalancesRequestDTO $request): BalancesReadResponseDTO
    {
        return $this->dispatch(__FUNCTION__, $request);
    }

    public function getStatements(GetStatementsRequestDTO $request): StatementReadResponseDTO
    {
        return $this->dispatch(__FUNCTION__, $request);
    }

    public function getStatement(GetStatementRequestDTO $request): StatementReadResponseDTO
    {
        return $this->dispatch(__FUNCTION__, $request);
    }

    public function getStatementTransactions(GetStatementTransactionsRequestDTO $request): TransactionReadResponseDTO
    {
        return $this->dispatch(__FUNCTION__, $request);
    }

    public function getAllStatements(GetAllStatementsRequestDTO $request): StatementReadResponseDTO
    {
        return $this->dispatch(__FUNCTION__, $request);
    }

    public function getIntraDayStatement(GetIntraDayStatementRequestDTO $request): TransactionReadResponseDTO
    {
        return $this->dispatch(__FUNCTION__, $request);
    }

    /**
     * Record the call, then satisfy it from the scripted map (return the DTO
     * or throw the scripted `\Throwable`).
     */
    private function dispatch(string $method, object $request): object
    {
        $this->called[] = $method;
        $this->received[] = $request;

        $scripted = $this->responses[$method]
            ?? throw new \LogicException("FakeStatementsApiClient: no scripted response for [{$method}].");

        if ($scripted instanceof \Throwable) {
            throw $scripted;
        }

        return $scripted;
    }
}
