<?php

declare(strict_types=1);

namespace App\Http\Controllers\StatementsAPI;

use App\Http\Controllers\Controller;
use App\Http\Requests\StatementsAPI\GetIntraDayStatementRequest;
use App\Http\Requests\StatementsAPI\GetStatementTransactionsRequest;
use App\Services\StatementsAPI\StatementService;
use Illuminate\Http\JsonResponse;

/**
 * Inbound facade for the ABSA Statements transactions operations.
 *
 * GET /api/v1/statements/accounts/{accountId}/statements/{statementId}/transactions → upstream GET /accounts/{accountId}/statements/{statementId}/transactions.
 * GET /api/v1/statements/accounts/{accountId}/intraday-statement                  → upstream GET /accounts/{accountId}/intraday-statement.
 */
final class StatementTransactionsController extends Controller
{
    public function __construct(
        private readonly StatementService $statements,
    ) {}

    public function index(GetStatementTransactionsRequest $request): JsonResponse
    {
        return response()->json(
            $this->statements->getStatementTransactions($request->dto())->toArray(),
        );
    }

    public function intraday(GetIntraDayStatementRequest $request): JsonResponse
    {
        return response()->json(
            $this->statements->getIntraDayStatement($request->dto())->toArray(),
        );
    }
}
