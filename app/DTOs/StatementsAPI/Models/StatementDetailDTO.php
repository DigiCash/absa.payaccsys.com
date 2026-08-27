<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Models;

use App\DTOs\StatementsAPI\Enums\StatementTypeCode;
use App\DTOs\StatementsAPI\Support\BaseDto;

/**
 * StatementDetail — further details on a statement resource.
 * Item of StatementReadDataResponse.Statement.
 */
final readonly class StatementDetailDTO extends BaseDto
{
     /**
     * @param  array<int, string>|null                 $StatementDescription
     * @param  array<int, StatementFeeDTO>|null        $StatementFee
     * @param  array<int, StatementAmountDTO>|null     $StatementAmount
     */
    public function __construct(
        public readonly ?string $AccountId = null,
        public readonly ?string $StatementId = null,
        public readonly ?string $StatementReference = null,
        public readonly ?StatementTypeCode $Type = null,
        public readonly ?string $StartDateTime = null,
        public readonly ?string $EndDateTime = null,
        public readonly ?string $CreationDateTime = null,
        public readonly ?array $StatementDescription = null,
        public readonly ?array $StatementFee = null,
        public readonly ?array $StatementAmount = null,
       ) {
       }

    public static function fromArray(array $data): static
       {
        return new static(
            AccountId: isset($data['AccountId']) ? (string) $data['AccountId'] : null,
            StatementId: isset($data['StatementId']) ? (string) $data['StatementId'] : null,
            StatementReference: isset($data['StatementReference']) ? (string) $data['StatementReference'] : null,
            Type: self::enum(StatementTypeCode::class, $data['Type'] ?? null),
            StartDateTime: isset($data['StartDateTime']) ? (string) $data['StartDateTime'] : null,
            EndDateTime: isset($data['EndDateTime']) ? (string) $data['EndDateTime'] : null,
            CreationDateTime: isset($data['CreationDateTime']) ? (string) $data['CreationDateTime'] : null,
            StatementDescription: isset($data['StatementDescription']) ? $data['StatementDescription'] : null,
            StatementFee: isset($data['StatementFee']) ? self::nestedAll($data['StatementFee'], StatementFeeDTO::class) : null,
            StatementAmount: isset($data['StatementAmount']) ? self::nestedAll($data['StatementAmount'], StatementAmountDTO::class) : null,
           );
       }

    public function toArray(): array
       {
        return array_filter([
              'AccountId' => $this->AccountId,
              'StatementId' => $this->StatementId,
              'StatementReference' => $this->StatementReference,
              'Type' => $this->Type?->value,
              'StartDateTime' => $this->StartDateTime,
              'EndDateTime' => $this->EndDateTime,
              'CreationDateTime' => $this->CreationDateTime,
              'StatementDescription' => $this->StatementDescription,
              'StatementFee' => $this->StatementFee !== null
               ? array_map(static fn (StatementFeeDTO $f) => $f->toArray(), $this->StatementFee)
               : null,
              'StatementAmount' => $this->StatementAmount !== null
               ? array_map(static fn (StatementAmountDTO $a) => $a->toArray(), $this->StatementAmount)
               : null,
           ], static fn ($value) => $value !== null);
       }
}
