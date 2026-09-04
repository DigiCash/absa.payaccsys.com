<?php

declare(strict_types=1);

namespace App\Http\Controllers\StatementsAPI;

use App\Http\Controllers\Controller;
use App\Http\Requests\StatementsAPI\GetAllStatementsRequest;
use App\Http\Requests\StatementsAPI\GetStatementRequest;
use App\Http\Requests\StatementsAPI\GetStatementsRequest;
use App\Services\StatementsAPI\StatementService;
use Illuminate\Http\JsonResponse;

/**
 * Inbound facade for the ABSA Statements statement operations.
 *
 * GET /api/v1/statements                                                 → upstream GET /statements (SA-only).
 * GET /api/v1/statements/accounts/{accountId}/statements                 → upstream GET /accounts/{accountId}/statements.
 * GET /api/v1/statements/accounts/{accountId}/statements/{statementId}   → upstream GET /accounts/{accountId}/statements/{statementId}.
 */
final class StatementsController extends Controller
{
    public function __construct(
        private readonly StatementService $statements,
    ) {}

    public function all(GetAllStatementsRequest $request): JsonResponse
    {
        return response()->json(
            $this->statements->getAllStatements($request->dto())->toArray(),
        );
    }

    public function index(GetStatementsRequest $request): JsonResponse
    {
        return response()->json(
            $this->statements->getStatements($request->dto())->toArray(),
        );
    }

    public function show(GetStatementRequest $request): JsonResponse
    {
        return response()->json(
            $this->statements->getStatement($request->dto())->toArray(),
        );
    }
}
