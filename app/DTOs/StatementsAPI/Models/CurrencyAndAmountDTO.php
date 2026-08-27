<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Models;

use App\DTOs\StatementsAPI\Support\BaseDto;

/**
 * CurrencyAndAmount — specifies an amount and a currency.
 * Amount is a MonetaryAmount string; Currency is an ISO 4217 CurrencyCode.
 */
final readonly class CurrencyAndAmountDTO extends BaseDto
{
    public function __construct(
        public readonly ?string $Amount = null,
        public readonly ?string $Currency = null,
      ) {
      }

    public static function fromArray(array $data): static
      {
        return new static(
            Amount: isset($data['Amount']) ? (string) $data['Amount'] : null,
            Currency: isset($data['Currency']) ? (string) $data['Currency'] : null,
          );
      }

    public function toArray(): array
      {
        return array_filter([
              'Amount' => $this->Amount,
              'Currency' => $this->Currency,
          ], static fn ($value) => $value !== null);
      }
}
