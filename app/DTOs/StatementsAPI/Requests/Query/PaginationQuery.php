<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Requests\Query;

use App\DTOs\StatementsAPI\Support\BaseDto;

/**
 * Shared pagination / cursor query parameters.
 *
 * index/time apply to Intra-Day statements (SA uses index, other regions use
 * time); pg/pgSize are standard page + page-size parameters. All optional.
 */
final readonly class PaginationQuery extends BaseDto
{
    public function __construct(
        public readonly ?int $index = null,
        public readonly ?string $time = null,
        public readonly ?int $pg = null,
        public readonly ?int $pgSize = null,
    ) {
    }

    public static function fromArray(array $data): static
    {
        return new static(
            index: isset($data['index']) ? (int) $data['index'] : null,
            time: $data['time'] ?? null,
            pg: isset($data['pg']) ? (int) $data['pg'] : null,
            pgSize: isset($data['pgSize']) ? (int) $data['pgSize'] : null,
        );
    }

    /**
     * @return array<string, int|string>
     */
    public function toArray(): array
    {
        return array_filter([
            'index' => $this->index,
            'time' => $this->time,
            'pg' => $this->pg,
            'pgSize' => $this->pgSize,
        ], static fn ($value) => $value !== null);
    }
}
