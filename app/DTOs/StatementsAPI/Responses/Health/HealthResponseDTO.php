<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Responses\Health;

use App\DTOs\StatementsAPI\Support\BaseDto;

/**
 * Health response — inline schema of GET /health ({ "status": string }).
 */
final readonly class HealthResponseDTO extends BaseDto
{
    public function __construct(
        public readonly ?string $status = null,
       ) {
       }

    public static function fromArray(array $data): static
       {
        return new static(
            status: isset($data['status']) ? (string) $data['status'] : null,
           );
       }

    public function toArray(): array
       {
        return array_filter([
              'status' => $this->status,
           ], static fn ($value) => $value !== null);
       }
}
