<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Responses\Balances;

use App\DTOs\StatementsAPI\Models\LinkDTO;
use App\DTOs\StatementsAPI\Models\MetadatumDTO;
use App\DTOs\StatementsAPI\Support\BaseDto;

/**
 * BalancesReadResponse — envelope returned by GET /balances and
 * GET /accounts/{accountId}/balances.
 */
final readonly class BalancesReadResponseDTO extends BaseDto
{
    /**
     * @param  array<int, LinkDTO>|null             $Links
     * @param  array<int, MetadatumDTO>|null        $Meta
     */
    public function __construct(
        public readonly BalancesReadDataDTO $Data,
        public readonly ?array $Links = null,
        public readonly ?array $Meta = null,
       ) {
       }

    public static function fromArray(array $data): static
       {
        return new static(
            Data: BalancesReadDataDTO::fromArray($data['Data'] ?? []),
            Links: isset($data['Links']) ? self::nestedAll($data['Links'], LinkDTO::class) : null,
            Meta: isset($data['Meta']) ? self::nestedAll($data['Meta'], MetadatumDTO::class) : null,
           );
       }

    public function toArray(): array
       {
        return array_filter([
              'Data' => $this->Data->toArray(),
              'Links' => $this->Links !== null
               ? array_map(static fn (LinkDTO $l) => $l->toArray(), $this->Links)
               : null,
              'Meta' => $this->Meta !== null
               ? array_map(static fn (MetadatumDTO $m) => $m->toArray(), $this->Meta)
               : null,
           ], static fn ($value) => $value !== null);
       }
}
