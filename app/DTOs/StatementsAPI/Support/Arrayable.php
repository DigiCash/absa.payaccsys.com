<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Support;

/**
 * A DTO that can be serialised back to the exact OpenAPI key shape
 * (case-sensitive, e.g. "CreditDebitIndicator", "FileCreationDateTime").
 */
interface Arrayable
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
