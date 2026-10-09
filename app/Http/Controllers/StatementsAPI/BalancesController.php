<?php

declare(strict_types=1);

namespace App\Http\Controllers\StatementsAPI;

use App\Http\Controllers\Controller;
use App\Http\Requests\StatementsAPI\GetAccountBalancesRequest;
use App\Http\Requests\StatementsAPI\GetBalancesRequest;
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
     */
    protected string $loggerName = 'ABSA API - BalancesController';

    public function __construct(private readonly StatementService $statements) {}

    /**
     * Get Balances for all accounts.
     * Market availability: Pan-Africa
     */
    public function index(GetBalancesRequest $request): JsonResponse
    {
        return response()->json($this->statements->getBalances($request->dto())->toArray());
    }

    /**
     * Get Balances for a specific account.
     * Market availability: Pan-Africa
     */
    public function show(GetAccountBalancesRequest $request): JsonResponse
    {
        $this->logDb->debug('GetAccountBalancesRequest Request: '.json_encode($request->toArray(), JSON_PRETTY_PRINT));

        return response()->json($this->statements->getAccountBalances($request->dto())->toArray());
    }
}
