<?php

declare(strict_types=1);

namespace App\Http\Controllers\StatementsAPI;

use App\DTOs\StatementsAPI\Requests\GetHealthRequestDTO;
use App\Http\Controllers\Controller;
use App\Services\StatementsAPI\StatementService;
use Illuminate\Http\JsonResponse;

/**
 * Inbound facade for the ABSA Statements health operation.
 *
 * GET /api/v1/statements/health → upstream GET /health.
 */
final class HealthController extends Controller
{
    public function __construct(
        private readonly StatementService $statements,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json(
            $this->statements->getHealth(new GetHealthRequestDTO)->toArray(),
        );
    }
}
