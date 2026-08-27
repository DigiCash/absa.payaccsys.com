<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Models;

use App\DTOs\StatementsAPI\Enums\CreditDebitCode;
use App\DTOs\StatementsAPI\Enums\EntryStatusCode;
use App\DTOs\StatementsAPI\Support\BaseDto;

/**
 * TransactionDetail — further details on an entry in the report.
 * Item of TransactionReadDataResponse.Transaction.
 */
final readonly class TransactionDetailDTO extends BaseDto
{
     /**
     * @param  array<int, string>|null            $StatementReference
     */
    public function __construct(
        public readonly ?string $AccountId = null,
        public readonly ?string $TransactionId = null,
        public readonly ?string $TransactionReference = null,
        public readonly ?array $StatementReference = null,
        public readonly ?CreditDebitCode $CreditDebitIndicator = null,
        public readonly ?EntryStatusCode $Status = null,
        public readonly ?string $BookingDateTime = null,
        public readonly ?string $ValueDate = null,
        public readonly ?string $TransactionInformation = null,
        public readonly ?CurrencyAndAmountDTO $Amount = null,
        public readonly ?CurrencyAndAmountDTO $ChargeAmount = null,
        public readonly ?BankTransactionCodeStructureDTO $BankTransactionCode = null,
        public readonly ?ProprietaryBankTransactionCodeStructureDTO $ProprietaryBankTransactionCode = null,
        public readonly ?TransactionCashBalanceDTO $Balance = null,
        public readonly ?SupplementaryDataDTO $SupplementaryData = null,
       ) {
       }

    public static function fromArray(array $data): static
       {
        return new static(
            AccountId: isset($data['AccountId']) ? (string) $data['AccountId'] : null,
            TransactionId: isset($data['TransactionId']) ? (string) $data['TransactionId'] : null,
            TransactionReference: isset($data['TransactionReference']) ? (string) $data['TransactionReference'] : null,
            StatementReference: isset($data['StatementReference']) ? $data['StatementReference'] : null,
            CreditDebitIndicator: self::enum(CreditDebitCode::class, $data['CreditDebitIndicator'] ?? null),
            Status: self::enum(EntryStatusCode::class, $data['Status'] ?? null),
            BookingDateTime: isset($data['BookingDateTime']) ? (string) $data['BookingDateTime'] : null,
            ValueDate: isset($data['ValueDate']) ? (string) $data['ValueDate'] : null,
            TransactionInformation: isset($data['TransactionInformation']) ? (string) $data['TransactionInformation'] : null,
            Amount: self::nested($data['Amount'] ?? null, CurrencyAndAmountDTO::class),
            ChargeAmount: self::nested($data['ChargeAmount'] ?? null, CurrencyAndAmountDTO::class),
            BankTransactionCode: self::nested($data['BankTransactionCode'] ?? null, BankTransactionCodeStructureDTO::class),
            ProprietaryBankTransactionCode: self::nested($data['ProprietaryBankTransactionCode'] ?? null, ProprietaryBankTransactionCodeStructureDTO::class),
            Balance: self::nested($data['Balance'] ?? null, TransactionCashBalanceDTO::class),
            SupplementaryData: self::nested($data['SupplementaryData'] ?? null, SupplementaryDataDTO::class),
           );
       }

    public function toArray(): array
       {
        return array_filter([
              'AccountId' => $this->AccountId,
              'TransactionId' => $this->TransactionId,
              'TransactionReference' => $this->TransactionReference,
              'StatementReference' => $this->StatementReference,
               'CreditDebitIndicator' => $this->CreditDebitIndicator?->value,
               'Status' => $this->Status?->value,
               'BookingDateTime' => $this->BookingDateTime,
               'ValueDate' => $this->ValueDate,
               'TransactionInformation' => $this->TransactionInformation,
               'Amount' => $this->Amount?->toArray(),
               'ChargeAmount' => $this->ChargeAmount?->toArray(),
               'BankTransactionCode' => $this->BankTransactionCode?->toArray(),
               'ProprietaryBankTransactionCode' => $this->ProprietaryBankTransactionCode?->toArray(),
               'Balance' => $this->Balance?->toArray(),
               'SupplementaryData' => $this->SupplementaryData?->toArray(),
           ], static fn ($value) => $value !== null);
       }
}
