<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Responses\StatementTransactions;

use App\DTOs\StatementsAPI\Models\TransactionDetailDTO;
use App\DTOs\StatementsAPI\Support\BaseDto;

/**
 * TransactionReadDataResponse — the Data payload of a transaction read.
 * Also used by the intraday-statement operation.
 */
final readonly class TransactionReadDataDTO extends BaseDto
{
    /**
     * @param  array<int, TransactionDetailDTO>|null   $Transaction
     */
    public function __construct(
        public readonly ?array $Transaction = null,
       ) {
       }

    public static function fromArray(array $data): static
       {
        return new static(
            Transaction: isset($data['Transaction']) ? self::nestedAll($data['Transaction'], TransactionDetailDTO::class) : null,
           );
       }

    public function toArray(): array
       {
        return array_filter([
              'Transaction' => $this->Transaction !== null
               ? array_map(static fn (TransactionDetailDTO $t) => $t->toArray(), $this->Transaction)
               : null,
           ], static fn ($value) => $value !== null);
       }
}
