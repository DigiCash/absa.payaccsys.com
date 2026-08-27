<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Models;

use App\DTOs\StatementsAPI\Support\BaseDto;

/**
 * SupplementaryData — an empty object in the spec; modelled as a placeholder to
 * preserve the field contract on TransactionDetail.
 */
final readonly class SupplementaryDataDTO extends BaseDto
{
    public function __construct()
    {
    }

    public static function fromArray(array $data): static
    {
        return new static();
    }

    public function toArray(): array
    {
        return [];
    }
}
