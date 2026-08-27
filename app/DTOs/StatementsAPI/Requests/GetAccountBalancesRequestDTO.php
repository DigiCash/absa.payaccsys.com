<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Requests;

use App\DTOs\StatementsAPI\Support\BaseDto;

/**
 * GetAccountBalances — GET /accounts/{accountId}/balances.
 */
final readonly class GetAccountBalancesRequestDTO extends BaseDto
{
    public function __construct(
        public readonly string $accountId,
        public readonly ?AbsaRequestHeaders $headers = null,
    ) {
    }

    public static function fromArray(array $data): static
    {
        return new static(
            accountId: (string) $data['accountId'],
            headers: isset($data['headers']) ? AbsaRequestHeaders::fromArray($data['headers']) : null,
        );
    }

    /**
     * @return array<string, string>
     */
    public function path(): array
    {
        return ['accountId' => $this->accountId];
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
     * @return array{path: array<string, string>, query: array<string, mixed>, headers: array<string, string>}
     */
    public function toArray(): array
    {
        return [
            'path' => $this->path(),
            'query' => $this->query(),
            'headers' => $this->headers(),
        ];
    }
}
