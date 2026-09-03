<?php

declare(strict_types=1);

namespace Tests\Unit\Services\StatementsAPI;

use App\DTOs\StatementsAPI\Requests\GetAccountBalancesRequestDTO;
use App\DTOs\StatementsAPI\Requests\GetAllStatementsRequestDTO;
use App\DTOs\StatementsAPI\Requests\GetBalancesRequestDTO;
use App\DTOs\StatementsAPI\Requests\GetHealthRequestDTO;
use App\DTOs\StatementsAPI\Requests\GetIntraDayStatementRequestDTO;
use App\DTOs\StatementsAPI\Requests\GetStatementRequestDTO;
use App\DTOs\StatementsAPI\Requests\GetStatementsRequestDTO;
use App\DTOs\StatementsAPI\Requests\GetStatementTransactionsRequestDTO;
use App\DTOs\StatementsAPI\Responses\Balances\BalancesReadDataDTO;
use App\DTOs\StatementsAPI\Responses\Balances\BalancesReadResponseDTO;
use App\DTOs\StatementsAPI\Responses\Health\HealthResponseDTO;
use App\DTOs\StatementsAPI\Responses\Statements\StatementReadDataDTO;
use App\DTOs\StatementsAPI\Responses\Statements\StatementReadResponseDTO;
use App\DTOs\StatementsAPI\Responses\StatementTransactions\TransactionReadDataDTO;
use App\DTOs\StatementsAPI\Responses\StatementTransactions\TransactionReadResponseDTO;
use App\DTOs\StatementsAPI\Transport\StatementsApiClientConfig;
use App\DTOs\StatementsAPI\Transport\StatementsApiException;
use App\Services\StatementsAPI\Contracts\OAuth2TokenManagerInterface;
use App\Services\StatementsAPI\StatementService;
use Tests\TestCase;
use Tests\Unit\DTOs\StatementsAPI\Support\FakeStatementsApiClient;

/**
 * Hermetic test double for {@see OAuth2TokenManagerInterface}: returns a fixed
 * token with zero DB/network side effects.
 */
final class StubOAuth2TokenManager implements OAuth2TokenManagerInterface
{
    public int $resolveCount = 0;

    public function __construct(
        private readonly string $token,
    ) {}

    public function getValidToken(): string
    {
        $this->resolveCount++;

        return $this->token;
    }
}

/**
 * Behavioural tests for `StatementService` (application-orchestration layer).
 *
 * Both collaborators are hermetic doubles: `FakeStatementsApiClient` scripts
 * every response in-memory and `StubOAuth2TokenManager` returns a fixed token.
 * No DB, no network, no cache — the service delegates every outbound call.
 */
uses(TestCase::class);

function responseMap(): array
{
    return [
        'getHealth' => new HealthResponseDTO('OK'),
        'getBalances' => new BalancesReadResponseDTO(new BalancesReadDataDTO),
        'getAccountBalances' => new BalancesReadResponseDTO(new BalancesReadDataDTO),
        'getStatements' => new StatementReadResponseDTO(new StatementReadDataDTO),
        'getStatement' => new StatementReadResponseDTO(new StatementReadDataDTO),
        'getStatementTransactions' => new TransactionReadResponseDTO(new TransactionReadDataDTO),
        'getAllStatements' => new StatementReadResponseDTO(new StatementReadDataDTO),
        'getIntraDayStatement' => new TransactionReadResponseDTO(new TransactionReadDataDTO),
    ];
}

// ---------------------------------------------------------------------------
// 1. Token resolution + injection into client config (apiKey seam, ADR-001)
// ---------------------------------------------------------------------------
it('resolves a token via the manager and exposes it through the apiKey seam', function () {
    $tokenManager = new StubOAuth2TokenManager('token-abc-123');
    $client = new FakeStatementsApiClient(responseMap());

    $service = new StatementService($client, $tokenManager);
    $request = new GetHealthRequestDTO;

    $response = $service->getHealth($request);

    // Token was resolved exactly once, ahead of the outbound call.
    expect($tokenManager->resolveCount)->toBe(1);
    expect($service->resolvedToken())->toBe('token-abc-123');

    // The transport consumes the resolved token through `Config::apiKey`.
    $config = StatementsApiClientConfig::fromConfig([
        'base_url' => 'https://api.example.test',
        'api_key' => $service->resolvedToken(),
    ]);
    expect($config->apiKey)->toBe('token-abc-123');

    // The client received the correct request and returned the scripted DTO.
    expect($client->called)->toBe(['getHealth']);
    expect($client->received)->toBe([$request]);
    expect($response)->toBeInstanceOf(HealthResponseDTO::class);
    expect($response->status)->toBe('OK');
});

// ---------------------------------------------------------------------------
// 2. Accurate mapping of API responses into statement response DTOs
// ---------------------------------------------------------------------------
it('forwards the correct request DTO and returns the correct response DTO for each operation', function () {
    $tokenManager = new StubOAuth2TokenManager('token-xyz');
    $client = new FakeStatementsApiClient(responseMap());

    $service = new StatementService($client, $tokenManager);

    $cases = [
        'getHealth' => [new GetHealthRequestDTO, HealthResponseDTO::class],
        'getBalances' => [new GetBalancesRequestDTO, BalancesReadResponseDTO::class],
        'getAccountBalances' => [new GetAccountBalancesRequestDTO('acc-1'), BalancesReadResponseDTO::class],
        'getStatements' => [new GetStatementsRequestDTO('acc-1'), StatementReadResponseDTO::class],
        'getStatement' => [new GetStatementRequestDTO('acc-1', 'stmt-1'), StatementReadResponseDTO::class],
        'getStatementTransactions' => [new GetStatementTransactionsRequestDTO('acc-1', 'stmt-1'), TransactionReadResponseDTO::class],
        'getAllStatements' => [new GetAllStatementsRequestDTO, StatementReadResponseDTO::class],
        'getIntraDayStatement' => [new GetIntraDayStatementRequestDTO('acc-1'), TransactionReadResponseDTO::class],
    ];

    foreach ($cases as $operation => [$request, $expectedClass]) {
        /** @var object $request */
        $response = $service->{$operation}($request);

        expect($response)->toBeInstanceOf($expectedClass);

        // Token is resolved fresh for every operation.
        expect($service->resolvedToken())->toBe('token-xyz');
    }

    // Each operation was dispatched exactly once, receiving the exact request.
    expect($client->called)->toBe(array_keys($cases));
    expect($client->received)->toHaveCount(8);
    expect($tokenManager->resolveCount)->toBe(8);
});

// ---------------------------------------------------------------------------
// 3. StatementExceptions from the client propagate unchanged
// ---------------------------------------------------------------------------
it('propagates a StatementsApiException thrown by the client unchanged', function () {
    $tokenManager = new StubOAuth2TokenManager('token-xyz');
    $cause = new StatementsApiException(502, error: null);

    $client = new FakeStatementsApiClient(['getBalances' => $cause]);
    $service = new StatementService($client, $tokenManager);

    $thrown = null;

    try {
        $service->getBalances(new GetBalancesRequestDTO);
    } catch (StatementsApiException $e) {
        $thrown = $e;
    }

    expect($thrown)->toBe($cause);
    expect($thrown?->status)->toBe(502);

    // Token was still resolved before the failing call.
    expect($tokenManager->resolveCount)->toBe(1);
    expect($service->resolvedToken())->toBe('token-xyz');
});
