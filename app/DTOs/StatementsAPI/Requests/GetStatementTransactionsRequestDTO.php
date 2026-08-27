<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Requests;

use App\DTOs\StatementsAPI\Requests\Query\PaginationQuery;
use App\DTOs\StatementsAPI\Support\BaseDto;

/**
 * GetTransactionsAccountIdStatementId —
 * GET /accounts/{accountId}/statements/{statementId}/transactions.
 */
final readonly class GetStatementTransactionsRequestDTO extends BaseDto
{
    public function __construct(
        public readonly string $accountId,
        public readonly string $statementId,
        public readonly ?PaginationQuery $pagination = null,
        public readonly ?AbsaRequestHeaders $headers = null,
     ) {
     }

    public static function fromArray(array $data): static
     {
        return new static(
            accountId: (string) $data['accountId'],
            statementId: (string) $data['statementId'],
            pagination: isset($data['pagination']) ? PaginationQuery::fromArray($data['pagination']) : null,
            headers: isset($data['headers']) ? AbsaRequestHeaders::fromArray($data['headers']) : null,
         );
     }

     /**
      * @return array<string, string>
      */
    public function path(): array
     {
        return [
             'accountId' => $this->accountId,
             'statementId' => $this->statementId,
         ];
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
