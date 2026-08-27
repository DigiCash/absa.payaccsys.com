<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Models;

use App\DTOs\StatementsAPI\Enums\BalanceTypeCode;
use App\DTOs\StatementsAPI\Enums\CreditDebitCode;
use App\DTOs\StatementsAPI\Support\BaseDto;

/**
 * TransactionCashBalance — the running balance as a net increase/decrease
 * after a transaction entry is applied. Item of TransactionDetail.Balance.
 */
final readonly class TransactionCashBalanceDTO extends BaseDto
{
    public function __construct(
        public readonly ?CreditDebitCode $CreditDebitIndicator = null,
        public readonly ?BalanceTypeCode $Type = null,
        public readonly ?CurrencyAndAmountDTO $Amount = null,
      ) {
      }

    public static function fromArray(array $data): static
      {
        return new static(
            CreditDebitIndicator: self::enum(CreditDebitCode::class, $data['CreditDebitIndicator'] ?? null),
            Type: self::enum(BalanceTypeCode::class, $data['Type'] ?? null),
            Amount: self::nested($data['Amount'] ?? null, CurrencyAndAmountDTO::class),
          );
      }

    public function toArray(): array
      {
        return array_filter([
              'CreditDebitIndicator' => $this->CreditDebitIndicator?->value,
              'Type' => $this->Type?->value,
              'Amount' => $this->Amount?->toArray(),
          ], static fn ($value) => $value !== null);
      }
}
