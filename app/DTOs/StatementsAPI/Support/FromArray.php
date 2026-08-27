<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Support;

/**
 * A DTO that can be hydrated from a raw decoded JSON array.
 * Keys are matched to OpenAPI property names case-sensitively.
 */
interface FromArray
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): static;
}
