<?php

declare(strict_types=1);

namespace App\Http\Controllers\StatementsAPI;

use App\Http\Controllers\Controller;
use App\Http\Requests\StatementsAPI\GetAllStatementsRequest;
use App\Http\Requests\StatementsAPI\GetStatementRequest;
use App\Http\Requests\StatementsAPI\GetStatementsRequest;
use App\Services\StatementsAPI\StatementService;
use App\Traits\InteractsWithDatabaseLog;
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
    use InteractsWithDatabaseLog;

    /**
     * Logger Name
     *
     * @var string
     */
    protected string $loggerName = 'ABSA API - StatementsController';

    public function __construct(
        private readonly StatementService $statements,
    ) {}

    /**
     *  Get All EOD Statements.
     *  Market Availability: SA-only
     *
     * @param GetAllStatementsRequest $request
     * @return JsonResponse
     */
    public function all(GetAllStatementsRequest $request): JsonResponse
    {
        $this->logDb->debug('GetAllStatementsRequest Request: ' . json_encode($request->toArray(), JSON_PRETTY_PRINT));
        return response()->json($this->statements->getAllStatements($request->dto())->toArray());
    }

    /**
     *  Get EOD Statements for account-id.
     *  Market Availability: SA-only
     *
     * @param GetStatementsRequest $request
     * @return JsonResponse
     */
    public function index(GetStatementsRequest $request): JsonResponse
    {
        $this->logDb->debug('GetStatementsRequests Request: ' . json_encode($request->toArray(), JSON_PRETTY_PRINT));
        return response()->json($this->statements->getStatements($request->dto())->toArray());
    }

    /**
     *  Get EOD Statement for account-id and statement-id.
     *  Market Availability: SA-only
     *
     * @param GetStatementRequest $request
     * @return JsonResponse
     */
    public function show(GetStatementRequest $request): JsonResponse
    {
        $this->logDb->debug('GetStatementRequest Request: ' . json_encode($request->toArray(), JSON_PRETTY_PRINT));
        return response()->json($this->statements->getStatement($request->dto())->toArray());
    }
}
