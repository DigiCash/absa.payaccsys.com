<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Models;

use App\DTOs\StatementsAPI\Enums\CreditLineTypeCode;
use App\DTOs\StatementsAPI\Support\BaseDto;

/**
 * CreditLineTypeDetail — a single credit line element. CreditLine is an array
 * of these (modelled as a typed list on BalancesDetail, not a wrapper DTO).
 */
final readonly class CreditLineTypeDetailDTO extends BaseDto
{
    public function __construct(
        public readonly ?bool $Included = null,
        public readonly ?CurrencyAndAmountDTO $Amount = null,
        public readonly ?CreditLineTypeCode $Type = null,
      ) {
      }

    public static function fromArray(array $data): static
      {
        return new static(
            Included: isset($data['Included']) ? (bool) $data['Included'] : null,
            Amount: self::nested($data['Amount'] ?? null, CurrencyAndAmountDTO::class),
            Type: self::enum(CreditLineTypeCode::class, $data['Type'] ?? null),
          );
      }

    public function toArray(): array
      {
        return array_filter([
              'Included' => $this->Included,
              'Amount' => $this->Amount?->toArray(),
              'Type' => $this->Type?->value,
          ], static fn ($value) => $value !== null);
      }
}
