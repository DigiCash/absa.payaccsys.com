<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Models;

use App\DTOs\StatementsAPI\Support\BaseDto;

/**
 * Metadatum — a single name/value metadata entry. Item of Meta.
 */
final readonly class MetadatumDTO extends BaseDto
{
    public function __construct(
        public readonly ?string $Name = null,
        public readonly mixed $Value = null,
       ) {
       }

    public static function fromArray(array $data): static
       {
        return new static(
            Name: isset($data['Name']) ? (string) $data['Name'] : null,
            Value: $data['Value'] ?? null,
           );
       }

    public function toArray(): array
       {
        return array_filter([
              'Name' => $this->Name,
              'Value' => $this->Value,
           ], static fn ($value) => $value !== null);
       }
}
