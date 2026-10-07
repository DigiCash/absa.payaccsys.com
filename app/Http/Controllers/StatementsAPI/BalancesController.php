<?php

declare(strict_types=1);

namespace App\Http\Controllers\StatementsAPI;

use App\DTOs\StatementsAPI\Requests\GetBalancesRequestDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\StatementsAPI\GetAccountBalancesRequest;
use App\Services\StatementsAPI\StatementService;
use App\Traits\InteractsWithDatabaseLog;
use Illuminate\Http\JsonResponse;

/**
 * Inbound facade for the ABSA Statements balances operations.
 *
 * GET /api/v1/statements/balances                 → upstream GET /balances.
 * GET /api/v1/statements/accounts/{accountId}/balances → upstream GET /accounts/{accountId}/balances.
 */
final class BalancesController extends Controller
{
    use InteractsWithDatabaseLog;

    /**
     * Logger Name
     *
     * @var string
     */
    protected string $loggerName = 'ABSA API - BalancesController';

    public function __construct(private readonly StatementService $statements)
    {

    }

    /**
     * Get Balances for all accounts.
     * Market availability: Pan-Africa
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return response()->json($this->statements->getBalances(new GetBalancesRequestDTO)->toArray());
    }

    /**
     * Get Balances for a specific account.
     * Market availability: Pan-Africa
     *
     * @param GetAccountBalancesRequest $request
     * @return JsonResponse
     */
    public function show(GetAccountBalancesRequest $request): JsonResponse
    {
        $this->logDb->debug('GetAccountBalancesRequest Request: ' . json_encode($request->toArray(), JSON_PRETTY_PRINT));
        return response()->json($this->statements->getAccountBalances($request->dto())->toArray());
    }
}
