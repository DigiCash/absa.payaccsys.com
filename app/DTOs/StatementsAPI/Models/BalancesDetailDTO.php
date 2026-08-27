<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Models;

use App\DTOs\StatementsAPI\Enums\CreditDebitCode;
use App\DTOs\StatementsAPI\Enums\ExternalBalanceType;
use App\DTOs\StatementsAPI\Support\BaseDto;

/**
 * BalancesDetail — further details on a balances resource.
 * Item of BalancesReadDataResponse.Balance.
 */
final readonly class BalancesDetailDTO extends BaseDto
{
    /**
     * @param  array<int, CreditLineTypeDetailDTO>|null   $CreditLine
     */
    public function __construct(
        public readonly ?string $AccountId = null,
        public readonly ?CurrencyAmountSubTypeDTO $Amount = null,
        public readonly ?CurrencyAmountSubTypeDTO $LocalAmount = null,
        public readonly ?CurrencyAndAmountDTO $TotalAmount = null,
        public readonly ?CreditDebitCode $CreditDebitIndicator = null,
        public readonly ?ExternalBalanceType $Type = null,
        public readonly ?string $DateTime = null,
        public readonly ?array $CreditLine = null,
      ) {
      }

    public static function fromArray(array $data): static
      {
        return new static(
            AccountId: isset($data['AccountId']) ? (string) $data['AccountId'] : null,
            Amount: self::nested($data['Amount'] ?? null, CurrencyAmountSubTypeDTO::class),
            LocalAmount: self::nested($data['LocalAmount'] ?? null, CurrencyAmountSubTypeDTO::class),
            TotalAmount: self::nested($data['TotalAmount'] ?? null, CurrencyAndAmountDTO::class),
            CreditDebitIndicator: self::enum(CreditDebitCode::class, $data['CreditDebitIndicator'] ?? null),
            Type: self::enum(ExternalBalanceType::class, $data['Type'] ?? null),
            DateTime: isset($data['DateTime']) ? (string) $data['DateTime'] : null,
            CreditLine: isset($data['CreditLine']) ? self::nestedAll($data['CreditLine'], CreditLineTypeDetailDTO::class) : null,
          );
      }

    public function toArray(): array
      {
        return array_filter([
              'AccountId' => $this->AccountId,
              'Amount' => $this->Amount?->toArray(),
              'LocalAmount' => $this->LocalAmount?->toArray(),
              'TotalAmount' => $this->TotalAmount?->toArray(),
              'CreditDebitIndicator' => $this->CreditDebitIndicator?->value,
              'Type' => $this->Type?->value,
              'DateTime' => $this->DateTime,
              'CreditLine' => $this->CreditLine !== null
              ? array_map(static fn (CreditLineTypeDetailDTO $c) => $c->toArray(), $this->CreditLine)
              : null,
          ], static fn ($value) => $value !== null);
      }
}
