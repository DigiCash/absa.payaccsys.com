<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Requests\Query;

use App\DTOs\StatementsAPI\Support\BaseDto;

/**
 * UTC ISO 8601 date-time range used to filter statements.
 * The time component is optional; set to 00:00:00 for date-only.
 */
final readonly class StatementDateRangeQuery extends BaseDto
{
    public function __construct(
        public readonly ?string $fromStatementDateTime = null,
        public readonly ?string $toStatementDateTime = null,
    ) {
    }

    public static function fromArray(array $data): static
    {
        return new static(
            fromStatementDateTime: $data['fromStatementDateTime'] ?? null,
            toStatementDateTime: $data['toStatementDateTime'] ?? null,
        );
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return array_filter([
            'fromStatementDateTime' => $this->fromStatementDateTime,
            'toStatementDateTime' => $this->toStatementDateTime,
        ], static fn ($value) => $value !== null);
    }
}
