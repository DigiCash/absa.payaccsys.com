<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Requests;

use App\DTOs\StatementsAPI\Requests\Query\PaginationQuery;
use App\DTOs\StatementsAPI\Support\BaseDto;

/**
 * GetBalances — GET /balances (Balances for all accounts).
 * Optional paging via `pg` / `pgSize` (ABSA returns `Links`/`Meta` pagination
 * metadata on this operation); plus the cross-cutting ABSA headers.
 */
final readonly class GetBalancesRequestDTO extends BaseDto
{
    public function __construct(
        public readonly ?PaginationQuery $pagination = null,
        public readonly ?AbsaRequestHeaders $headers = null,
    ) {}

    public static function fromArray(array $data): static
    {
        return new self(
            pagination: isset($data['pagination']) ? PaginationQuery::fromArray($data['pagination']) : null,
            headers: isset($data['headers']) ? AbsaRequestHeaders::fromArray($data['headers']) : null,
        );
    }

    /**
     * @return array<string, string>
     */
    public function headers(): array
    {
        return $this->headers?->toArray() ?? [];
    }

    /**
     * @return array<string, int|string>
     */
    public function query(): array
    {
        return $this->pagination?->toArray() ?? [];
    }

    /**
     * @return array{query: array<string, int|string>, headers: array<string, string>}
     */
    public function toArray(): array
    {
        return [
            'query' => $this->query(),
            'headers' => $this->headers(),
        ];
    }
}
