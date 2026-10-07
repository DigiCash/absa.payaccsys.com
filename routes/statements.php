<?php

declare(strict_types=1);

use App\Http\Controllers\StatementsAPI\BalancesController;
use App\Http\Controllers\StatementsAPI\HealthController;
use App\Http\Controllers\StatementsAPI\StatementsController;
use App\Http\Controllers\StatementsAPI\StatementTransactionsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Statements Facade Routes (/api/v1/statements/*)
|--------------------------------------------------------------------------
|
| Inbound facade for the 8 ABSA Statements operations. Every route is
| protected by Sanctum; the outbound credential is resolved by
| `StatementService` (OAuth2 Client Credentials / static key, ADR-001).
|
*/

Route::prefix('api/v1/statements')->middleware('auth:sanctum')->group(function (): void {
    // Health
    Route::get('/health', [HealthController::class, 'index']);

    // Balances
    Route::get('/balances', [BalancesController::class, 'index']);
    Route::get('/accounts/{accountId}/balances', [BalancesController::class, 'show']);

    // Statements
    Route::get('/', [StatementsController::class, 'all']);
    Route::get('/accounts/{accountId}/statements', [StatementsController::class, 'index']);
    Route::get('/accounts/{accountId}/statements/{statementId}', [StatementsController::class, 'show']);

    // Statement transactions
    Route::get('/accounts/{accountId}/statements/{statementId}/transactions', [StatementTransactionsController::class, 'index']);
    Route::get('/accounts/{accountId}/intraday-statement', [StatementTransactionsController::class, 'intraday']);
});
