<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Responses\Balances;

use App\DTOs\StatementsAPI\Models\BalancesDetailDTO;
use App\DTOs\StatementsAPI\Support\BaseDto;

/**
 * BalancesReadDataResponse — the Data payload of a balances read.
 */
final readonly class BalancesReadDataDTO extends BaseDto
{
    /**
     * @param  array<int, BalancesDetailDTO>|null   $Balance
     */
    public function __construct(
        public readonly ?array $Balance = null,
       ) {
       }

    public static function fromArray(array $data): static
       {
        return new static(
            Balance: isset($data['Balance']) ? self::nestedAll($data['Balance'], BalancesDetailDTO::class) : null,
           );
       }

    public function toArray(): array
       {
        return array_filter([
              'Balance' => $this->Balance !== null
               ? array_map(static fn (BalancesDetailDTO $b) => $b->toArray(), $this->Balance)
               : null,
           ], static fn ($value) => $value !== null);
       }
}
