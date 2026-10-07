<?php

declare(strict_types=1);

namespace App\Http\Requests\StatementsAPI;

use App\DTOs\StatementsAPI\Requests\GetStatementTransactionsRequestDTO;
use App\DTOs\StatementsAPI\Requests\Query\PaginationQuery;

/**
 * Validates GET /api/v1/statements/accounts/{accountId}/statements/{statementId}/transactions.
 */
final class GetStatementTransactionsRequest extends AbstractStatementsRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'accountId' => ['required', 'string', 'max:255'],
            'statementId' => ['required', 'string', 'max:255'],
            'pg' => ['nullable', 'integer', 'min:1'],
            'pgSize' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function dto(): GetStatementTransactionsRequestDTO
    {
        $validated = $this->validated();

        $pg = $validated['pg'] ?? null;
        $pgSize = $validated['pgSize'] ?? null;

        return new GetStatementTransactionsRequestDTO(
            accountId: (string) $validated['accountId'],
            statementId: (string) $validated['statementId'],
            pagination: $pg !== null || $pgSize !== null
                ? new PaginationQuery(
                    pg: $pg !== null ? (int) $pg : null,
                    pgSize: $pgSize !== null ? (int) $pgSize : null,
                )
                : null,
            headers: $this->absaHeaders(),
        );
    }
}
