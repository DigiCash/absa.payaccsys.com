<?php

declare(strict_types=1);

namespace App\Http\Requests\StatementsAPI;

use App\DTOs\StatementsAPI\Requests\GetBalancesRequestDTO;
use App\DTOs\StatementsAPI\Requests\Query\PaginationQuery;

/**
 * Validates GET /api/v1/statements/balances.
 *
 * Optional paging: `pg` / `pgSize` (forwarded to the ABSA balances operation,
 * which returns `Links`/`Meta` pagination metadata).
 */
final class GetBalancesRequest extends AbstractStatementsRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'pg' => ['nullable', 'integer', 'min:1'],
            'pgSize' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function dto(): GetBalancesRequestDTO
    {
        return new GetBalancesRequestDTO(
            pagination: $this->pagination($this->validated()),
            headers: $this->absaHeaders(),
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
