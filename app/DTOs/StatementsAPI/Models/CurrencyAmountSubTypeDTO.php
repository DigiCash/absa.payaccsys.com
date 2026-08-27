<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Models;

use App\DTOs\StatementsAPI\Enums\BalanceSubType;
use App\DTOs\StatementsAPI\Support\BaseDto;

/**
 * CurrencyAmountSubType — an amount, currency and optional balance sub type.
 */
final readonly class CurrencyAmountSubTypeDTO extends BaseDto
{
    public function __construct(
        public readonly ?string $Amount = null,
        public readonly ?string $Currency = null,
        public readonly ?BalanceSubType $SubType = null,
      ) {
      }

    public static function fromArray(array $data): static
      {
        return new static(
            Amount: isset($data['Amount']) ? (string) $data['Amount'] : null,
            Currency: isset($data['Currency']) ? (string) $data['Currency'] : null,
            SubType: self::enum(BalanceSubType::class, $data['SubType'] ?? null),
          );
      }

    public function toArray(): array
      {
        return array_filter([
              'Amount' => $this->Amount,
              'Currency' => $this->Currency,
              'SubType' => $this->SubType?->value,
          ], static fn ($value) => $value !== null);
      }
}
