<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Support;

use App\DTOs\StatementsAPI\Requests\GetAccountBalancesRequestDTO;
use App\DTOs\StatementsAPI\Requests\GetAllStatementsRequestDTO;
use App\DTOs\StatementsAPI\Requests\GetBalancesRequestDTO;
use App\DTOs\StatementsAPI\Requests\GetHealthRequestDTO;
use App\DTOs\StatementsAPI\Requests\GetIntraDayStatementRequestDTO;
use App\DTOs\StatementsAPI\Requests\GetStatementRequestDTO;
use App\DTOs\StatementsAPI\Requests\GetStatementTransactionsRequestDTO;
use App\DTOs\StatementsAPI\Requests\GetStatementsRequestDTO;
use App\DTOs\StatementsAPI\Responses\Balances\BalancesReadResponseDTO;
use App\DTOs\StatementsAPI\Responses\Health\HealthResponseDTO;
use App\DTOs\StatementsAPI\Responses\Statements\StatementReadResponseDTO;
use App\DTOs\StatementsAPI\Responses\StatementTransactions\TransactionReadResponseDTO;

/**
 * The 8-operation transport contract for the ABSA Statements Facade API.
 * Callers depend on this interface, never on Guzzle / Illuminate\Http\Client.
 */
interface StatementsApiClientInterface
{
    public function getHealth(GetHealthRequestDTO $request): HealthResponseDTO;
    public function getBalances(GetBalancesRequestDTO $request): BalancesReadResponseDTO;
    public function getAccountBalances(GetAccountBalancesRequestDTO $request): BalancesReadResponseDTO;
    public function getStatements(GetStatementsRequestDTO $request): StatementReadResponseDTO;
    public function getStatement(GetStatementRequestDTO $request): StatementReadResponseDTO;
    public function getStatementTransactions(GetStatementTransactionsRequestDTO $request): TransactionReadResponseDTO;
    public function getAllStatements(GetAllStatementsRequestDTO $request): StatementReadResponseDTO;
    public function getIntraDayStatement(GetIntraDayStatementRequestDTO $request): TransactionReadResponseDTO;
}
