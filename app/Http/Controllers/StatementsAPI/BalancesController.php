<?php

declare(strict_types=1);

namespace App\Http\Controllers\StatementsAPI;

use App\DTOs\StatementsAPI\Requests\GetBalancesRequestDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\StatementsAPI\GetAccountBalancesRequest;
use App\Services\StatementsAPI\StatementService;
use Illuminate\Http\JsonResponse;

/**
 * Inbound facade for the ABSA Statements balances operations.
 *
 * GET /api/v1/statements/balances                 → upstream GET /balances.
 * GET /api/v1/statements/accounts/{accountId}/balances → upstream GET /accounts/{accountId}/balances.
 */
final class BalancesController extends Controller
{
    public function __construct(
        private readonly StatementService $statements,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json(
            $this->statements->getBalances(new GetBalancesRequestDTO)->toArray(),
        );
    }

    public function show(GetAccountBalancesRequest $request): JsonResponse
    {
        return response()->json(
            $this->statements->getAccountBalances($request->dto())->toArray(),
        );
    }
}
