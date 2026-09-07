<?php

declare(strict_types=1);

namespace App\Services\StatementsAPI;

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
use App\DTOs\StatementsAPI\Transport\StatementsApiException;
use App\Services\StatementsAPI\Contracts\OAuth2TokenManagerInterface;
use App\Traits\InteractsWithDatabaseLog;

/**
 * Application service orchestrating the eight ABSA Statements operations.
 *
 * It owns the two seams that the transport must never see:
 *
 *  1. Credentials — the active bearer token is resolved from
 *     {@see OAuth2TokenManagerInterface} ahead of every outbound call. In the
 *     transport the token is threaded to the ABSA endpoint through the
 *     `StatementsApiClientConfig::apiKey` seam (ADR-001); callers of this
 *     service depend only on {@see StatementsApiClientInterface}.
 *  2. Failures — a transport failure surfaces as the typed
 *     {@see StatementsApiException} (status + decoded `ErrorResponseDTO`) and
 *     is propagated unchanged so the inbound HTTP layer can translate it
 *     without ever catching generic HTTP/connection exceptions.
 *
 * Zero DB / network here: all calls delegate to the injected client, so unit
 * tests can substitute a hermetic double.
 */
final class StatementService
{
    use InteractsWithDatabaseLog;

    /**
     * Logger Name
     *
     * @var string
     */
    protected string $loggerName = 'ABSA API - StatementService';
    /**
     * The token resolved for the most recent operation (observability/tests).
     *
     * @var string|null
     */
    private ?string $resolvedToken = null;

    public function __construct(
        private readonly StatementsApiClientInterface $client,
        private readonly OAuth2TokenManagerInterface $tokenManager,
    )
    {

    }

    /**
     * Get Service Health
     *
     * @param GetHealthRequestDTO $request
     * @return HealthResponseDTO
     */
    public function getHealth(GetHealthRequestDTO $request): HealthResponseDTO
    {
        $this->resolveToken();
        return $this->client->getHealth($request);
    }

    /**
     * Get Balances for all accounts.
     * Market availability: Pan-Africa
     *
     * @param GetBalancesRequestDTO $request
     * @return BalancesReadResponseDTO
     */
    public function getBalances(GetBalancesRequestDTO $request): BalancesReadResponseDTO
    {
        $this->resolveToken();

        return $this->client->getBalances($request);
    }

    /**
     * Get Balances for a specific account.
     * Market availability: Pan-Africa
     *
     * @param GetAccountBalancesRequestDTO $request
     * @return BalancesReadResponseDTO
     */
    public function getAccountBalances(GetAccountBalancesRequestDTO $request): BalancesReadResponseDTO
    {
        $this->resolveToken();

        return $this->client->getAccountBalances($request);
    }

    /**
     * Get EOD Statements for account-id.
     * Market Availability: SA-only
     *
     * @param GetStatementsRequestDTO $request
     * @return StatementReadResponseDTO
     */
    public function getStatements(GetStatementsRequestDTO $request): StatementReadResponseDTO
    {
        $this->resolveToken();

        return $this->client->getStatements($request);
    }

    /**
     * Get EOD Statement for account-id and statement-id.
     * Market Availability: SA-only
     *
     * @param GetStatementRequestDTO $request
     * @return StatementReadResponseDTO
     */
    public function getStatement(GetStatementRequestDTO $request): StatementReadResponseDTO
    {
        $this->resolveToken();

        return $this->client->getStatement($request);
    }

    /**
     * Get EOD Transactions for account-id and statement-id.
     * Market Availability: SA-only
     *
     * @param GetStatementTransactionsRequestDTO $request
     * @return TransactionReadResponseDTO
     */
    public function getStatementTransactions(GetStatementTransactionsRequestDTO $request): TransactionReadResponseDTO
    {
        $this->resolveToken();

        return $this->client->getStatementTransactions($request);
    }

    /**
     * Get All EOD Statements.
     * Market Availability: SA-only
     *
     * @param GetAllStatementsRequestDTO $request
     * @return StatementReadResponseDTO
     */
    public function getAllStatements(GetAllStatementsRequestDTO $request): StatementReadResponseDTO
    {
        $this->resolveToken();

        return $this->client->getAllStatements($request);
    }

    /**
     * Get Today's Intra-Day Statement
     * Market Availability: All African Countries excluding
     *
     * @param GetIntraDayStatementRequestDTO $request
     * @return TransactionReadResponseDTO
     */
    public function getIntraDayStatement(GetIntraDayStatementRequestDTO $request): TransactionReadResponseDTO
    {
        $this->resolveToken();

        return $this->client->getIntraDayStatement($request);
    }

    /**
     * The token resolved for the most recent operation, or `null` when no
     * operation has run yet. Exposed so wiring / tests can confirm the active
     * credential that feeds the transport's `apiKey` seam.
     * @return string|null
     */
    public function resolvedToken(): ?string
    {
        return $this->resolvedToken;
    }

    /**
     * Resolve a valid bearer token from the credential manager before the
     * outbound call executes (ADR-001). Any acquisition failure surfaces as a
     * {@see StatementsApiException}-style typed error from the manager itself.
     * @return void
     */
    private function resolveToken(): void
    {
        $this->resolvedToken = $this->tokenManager->getValidToken();
    }
}
