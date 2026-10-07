<?php

declare(strict_types=1);

namespace App\Http\Requests\StatementsAPI;

use App\DTOs\StatementsAPI\Requests\GetIntraDayStatementRequestDTO;
use App\DTOs\StatementsAPI\Requests\Query\PaginationQuery;

/**
 * Validates GET /api/v1/statements/accounts/{accountId}/intraday-statement.
 *
 * `index` applies to the South-Africa region; `time` (HH:MM:SS) to regions
 * outside South Africa. `pg` / `pgSize` page the intraday cursor.
 */
final class GetIntraDayStatementRequest extends AbstractStatementsRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'accountId' => ['required', 'string', 'max:255'],
            'index' => ['nullable', 'integer', 'min:0'],
            'time' => ['nullable', 'date_format:H:i:s'],
            'pg' => ['nullable', 'integer', 'min:1'],
            'pgSize' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function dto(): GetIntraDayStatementRequestDTO
    {
        $validated = $this->validated();

        $index = $validated['index'] ?? null;
        $time = $validated['time'] ?? null;
        $pg = $validated['pg'] ?? null;
        $pgSize = $validated['pgSize'] ?? null;

        return new GetIntraDayStatementRequestDTO(
            accountId: (string) $validated['accountId'],
            pagination: $index !== null || $time !== null || $pg !== null || $pgSize !== null
                ? new PaginationQuery(
                    index: $index !== null ? (int) $index : null,
                    time: $time,
                    pg: $pg !== null ? (int) $pg : null,
                    pgSize: $pgSize !== null ? (int) $pgSize : null,
                )
                : null,
            headers: $this->absaHeaders(),
        );
    }
}
