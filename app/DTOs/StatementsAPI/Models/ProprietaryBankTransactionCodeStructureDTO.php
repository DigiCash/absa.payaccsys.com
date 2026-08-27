<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Models;

use App\DTOs\StatementsAPI\Support\BaseDto;

/**
 * ProprietaryBankTransactionCodeStructure — elements to fully identify a
 * proprietary bank transaction code.
 */
final readonly class ProprietaryBankTransactionCodeStructureDTO extends BaseDto
{
    public function __construct(
        public readonly ?string $Code = null,
        public readonly ?string $Issuer = null,
       ) {
       }

    public static function fromArray(array $data): static
       {
        return new static(
            Code: isset($data['Code']) ? (string) $data['Code'] : null,
            Issuer: isset($data['Issuer']) ? (string) $data['Issuer'] : null,
           );
       }

    public function toArray(): array
       {
        return array_filter([
              'Code' => $this->Code,
               'Issuer' => $this->Issuer,
           ], static fn ($value) => $value !== null);
       }
}
