<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Requests;

use App\DTOs\StatementsAPI\Requests\Query\PaginationQuery;
use App\DTOs\StatementsAPI\Requests\Query\StatementDateRangeQuery;
use App\DTOs\StatementsAPI\Support\BaseDto;

/**
 * GetStatementsAccountIdFromDateToDate —
 * GET /accounts/{accountId}/statements (EOD statements for an account).
 */
final readonly class GetStatementsRequestDTO extends BaseDto
{
    public function __construct(
        public readonly string $accountId,
        public readonly ?StatementDateRangeQuery $dateRange = null,
        public readonly ?PaginationQuery $pagination = null,
        public readonly ?AbsaRequestHeaders $headers = null,
    ) {
     }

    public static function fromArray(array $data): static
     {
        return new static(
            accountId: (string) $data['accountId'],
            dateRange: isset($data['dateRange']) ? StatementDateRangeQuery::fromArray($data['dateRange']) : null,
            pagination: isset($data['pagination']) ? PaginationQuery::fromArray($data['pagination']) : null,
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
      * @return array<string, int|string>
      */
    public function query(): array
     {
        return array_merge(
            $this->dateRange?->toArray() ?? [],
            $this->pagination?->toArray() ?? [],
         );
     }

     /**
      * @return array{path: array<string, string>, query: array<string, int|string>, headers: array<string, string>}
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
