<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Models;

use App\DTOs\StatementsAPI\Support\BaseDto;

/**
 * Link — a single link relevant to the payload. Item of Links.
 */
final readonly class LinkDTO extends BaseDto
{
    public function __construct(
        public readonly ?string $Rel = null,
        public readonly ?string $Href = null,
       ) {
       }

    public static function fromArray(array $data): static
       {
        return new static(
            Rel: isset($data['Rel']) ? (string) $data['Rel'] : null,
            Href: isset($data['Href']) ? (string) $data['Href'] : null,
           );
       }

    public function toArray(): array
       {
        return array_filter([
              'Rel' => $this->Rel,
               'Href' => $this->Href,
           ], static fn ($value) => $value !== null);
       }
}
