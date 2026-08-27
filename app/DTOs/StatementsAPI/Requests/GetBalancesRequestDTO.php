<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Requests;

use App\DTOs\StatementsAPI\Support\BaseDto;

/**
 * GetBalances — GET /balances (Balances for all accounts).
 * No path/query parameters, only the cross-cutting ABSA headers.
 */
final readonly class GetBalancesRequestDTO extends BaseDto
{
    public function __construct(
        public readonly ?AbsaRequestHeaders $headers = null,
    ) {
    }

    public static function fromArray(array $data): static
    {
        return new static(
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
     * @return array<string, mixed>
     */
    public function query(): array
    {
        return [];
    }

    /**
     * @return array{query: array<string, mixed>, headers: array<string, string>}
     */
    public function toArray(): array
    {
        return [
            'query' => $this->query(),
            'headers' => $this->headers(),
        ];
    }
}
