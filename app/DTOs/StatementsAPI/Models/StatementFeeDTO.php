<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Models;

use App\DTOs\StatementsAPI\Enums\CreditDebitCode;
use App\DTOs\StatementsAPI\Enums\StatementFeeFrequencyCode;
use App\DTOs\StatementsAPI\Enums\StatementFeeTypeCode;
use App\DTOs\StatementsAPI\Support\BaseDto;

/**
 * StatementFee (inline in StatementDetail) — a fee for a statement resource.
 */
final readonly class StatementFeeDTO extends BaseDto
{
    public function __construct(
        public readonly ?string $Description = null,
        public readonly ?CreditDebitCode $CreditDebitIndicator = null,
        public readonly ?StatementFeeTypeCode $Type = null,
        public readonly ?StatementFeeFrequencyCode $Frequency = null,
        public readonly ?CurrencyAndAmountDTO $Amount = null,
      ) {
      }

    public static function fromArray(array $data): static
      {
        return new static(
            Description: isset($data['Description']) ? (string) $data['Description'] : null,
            CreditDebitIndicator: self::enum(CreditDebitCode::class, $data['CreditDebitIndicator'] ?? null),
            Type: self::enum(StatementFeeTypeCode::class, $data['Type'] ?? null),
            Frequency: self::enum(StatementFeeFrequencyCode::class, $data['Frequency'] ?? null),
            Amount: self::nested($data['Amount'] ?? null, CurrencyAndAmountDTO::class),
          );
      }

    public function toArray(): array
      {
        return array_filter([
              'Description' => $this->Description,
              'CreditDebitIndicator' => $this->CreditDebitIndicator?->value,
              'Type' => $this->Type?->value,
              'Frequency' => $this->Frequency?->value,
              'Amount' => $this->Amount?->toArray(),
          ], static fn ($value) => $value !== null);
      }
}
