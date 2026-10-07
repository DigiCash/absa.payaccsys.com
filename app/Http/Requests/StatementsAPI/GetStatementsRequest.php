<?php

declare(strict_types=1);

namespace App\Http\Requests\StatementsAPI;

use App\DTOs\StatementsAPI\Requests\GetStatementsRequestDTO;
use App\DTOs\StatementsAPI\Requests\Query\PaginationQuery;
use App\DTOs\StatementsAPI\Requests\Query\StatementDateRangeQuery;

/**
 * Validates GET /api/v1/statements/accounts/{accountId}/statements.
 *
 * Optional filter: `fromStatementDateTime` / `toStatementDateTime` (UTC ISO 8601,
 * time component optional). Optional paging: `pg` / `pgSize`.
 */
final class GetStatementsRequest extends AbstractStatementsRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'accountId' => ['required', 'string', 'max:255'],
            'fromStatementDateTime' => ['nullable', 'date'],
            'toStatementDateTime' => ['nullable', 'date', 'after_or_equal:fromStatementDateTime'],
            'pg' => ['nullable', 'integer', 'min:1'],
            'pgSize' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function dto(): GetStatementsRequestDTO
    {
        $validated = $this->validated();

        return new GetStatementsRequestDTO(
            accountId: (string) $validated['accountId'],
            dateRange: $this->dateRange($validated),
            pagination: $this->pagination($validated),
            headers: $this->absaHeaders(),
        );
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function dateRange(array $validated): ?StatementDateRangeQuery
    {
        $from = $validated['fromStatementDateTime'] ?? null;
        $to = $validated['toStatementDateTime'] ?? null;

        if ($from === null && $to === null) {
            return null;
        }

        return new StatementDateRangeQuery(
            fromStatementDateTime: $from,
            toStatementDateTime: $to,
        );
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function pagination(array $validated): ?PaginationQuery
    {
        $pg = $validated['pg'] ?? null;
        $pgSize = $validated['pgSize'] ?? null;

        if ($pg === null && $pgSize === null) {
            return null;
        }

        return new PaginationQuery(
            pg: $pg !== null ? (int) $pg : null,
            pgSize: $pgSize !== null ? (int) $pgSize : null,
        );
    }
}
