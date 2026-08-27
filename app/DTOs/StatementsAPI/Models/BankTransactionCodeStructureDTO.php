<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Models;

use App\DTOs\StatementsAPI\Support\BaseDto;

/**
 * BankTransactionCodeStructure — elements to fully identify the type of the
 * underlying transaction resulting in an entry.
 */
final readonly class BankTransactionCodeStructureDTO extends BaseDto
{
    public function __construct(
        public readonly ?string $Code = null,
        public readonly ?string $SubCode = null,
       ) {
       }

    public static function fromArray(array $data): static
       {
        return new static(
            Code: isset($data['Code']) ? (string) $data['Code'] : null,
            SubCode: isset($data['SubCode']) ? (string) $data['SubCode'] : null,
           );
       }

    public function toArray(): array
       {
        return array_filter([
              'Code' => $this->Code,
              'SubCode' => $this->SubCode,
           ], static fn ($value) => $value !== null);
       }
}
