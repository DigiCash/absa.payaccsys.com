<?php

declare(strict_types=1);

namespace App\Http\Controllers\StatementsAPI;

use App\Http\Controllers\Controller;
use App\Http\Requests\StatementsAPI\GetIntraDayStatementRequest;
use App\Http\Requests\StatementsAPI\GetStatementTransactionsRequest;
use App\Services\StatementsAPI\StatementService;
use App\Traits\InteractsWithDatabaseLog;
use Illuminate\Http\JsonResponse;

/**
 * Inbound facade for the ABSA Statements transactions operations.
 *
 * GET /api/v1/statements/accounts/{accountId}/statements/{statementId}/transactions → upstream GET /accounts/{accountId}/statements/{statementId}/transactions.
 * GET /api/v1/statements/accounts/{accountId}/intraday-statement                  → upstream GET /accounts/{accountId}/intraday-statement.
 */
final class StatementTransactionsController extends Controller
{
    use InteractsWithDatabaseLog;

    /**
     * Logger Name
     *
     * @var string
     */
    protected string $loggerName = 'ABSA API - StatementTransactionsController';

    public function __construct(private readonly StatementService $statements)
    {}

    /**
     *  Get EOD Transactions for account-id and statement-id.
     *  Market Availability: SA-only
     *
     * @param GetStatementTransactionsRequest $request
     * @return JsonResponse
     */
    public function index(GetStatementTransactionsRequest $request): JsonResponse
    {
        $this->logDb->debug('GetStatementTransactionsRequest Request: ' . json_encode($request->toArray(), JSON_PRETTY_PRINT));

        return response()->json(
            $this->statements->getStatementTransactions($request->dto())->toArray(),
        );
    }

    /**
     * Get Today's Intra-Day Statement
     *  Market Availability: All African Countries excluding
     *
     * @param GetIntraDayStatementRequest $request
     * @return JsonResponse
     */
    public function intraday(GetIntraDayStatementRequest $request): JsonResponse
    {
        $this->logDb->debug('GetIntraDayStatementRequest Request: ' . json_encode($request->toArray(), JSON_PRETTY_PRINT));

        return response()->json(
            $this->statements->getIntraDayStatement($request->dto())->toArray(),
        );
    }
}
