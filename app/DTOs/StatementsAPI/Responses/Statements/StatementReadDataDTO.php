<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Responses\Statements;

use App\DTOs\StatementsAPI\Models\StatementDetailDTO;
use App\DTOs\StatementsAPI\Support\BaseDto;

/**
 * StatementReadDataResponse — the Data payload of a statement read.
 */
final readonly class StatementReadDataDTO extends BaseDto
{
    /**
     * @param  array<int, StatementDetailDTO>|null   $Statement
     */
    public function __construct(
        public readonly ?array $Statement = null,
       ) {
       }

    public static function fromArray(array $data): static
       {
        return new static(
            Statement: isset($data['Statement']) ? self::nestedAll($data['Statement'], StatementDetailDTO::class) : null,
           );
       }

    public function toArray(): array
       {
        return array_filter([
              'Statement' => $this->Statement !== null
               ? array_map(static fn (StatementDetailDTO $s) => $s->toArray(), $this->Statement)
               : null,
           ], static fn ($value) => $value !== null);
       }
}
