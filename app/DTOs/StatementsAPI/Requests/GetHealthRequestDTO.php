<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Requests;

use App\DTOs\StatementsAPI\Support\BaseDto;

/**
 * GetHealth — GET /health. No path/query parameters in the spec.
 */
final readonly class GetHealthRequestDTO extends BaseDto
{
    public function __construct(
        public readonly ?AbsaRequestHeaders $headers = null,
     ) {
     }

    public static function fromArray(array $data): static
     {
        return new GetHealthRequestDTO(
            headers: isset($data['headers']) ? AbsaRequestHeaders::fromArray($data['headers']) : null,
         );
     }

     /**
      * @return array<string, string>
      */
    public function headers(): array
     {
        return $this->headers?->toArray() ?? [];
     }

     /**
      * @return array<string, mixed>
      */
    public function query(): array
     {
        return [];
     }

     /**
      * @return array{query: array<string, mixed>, headers: array<string, string>}
      */
    public function toArray(): array
     {
        return [
             'query' => $this->query(),
             'headers' => $this->headers(),
         ];
     }
}
