<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Errors;

use App\DTOs\StatementsAPI\Support\BaseDto;

/**
 * ErrorResponse — the single shared error payload for all 4xx/5xx responses
 * (400/401/403/404/405/406/429/500). HTTP code mapping is the caller's
 * concern; this models the body only.
 */
final readonly class ErrorResponseDTO extends BaseDto
{
    /**
     * @param  array<int, ErrorDetailDTO>|null   $Errors
     */
    public function __construct(
        public readonly ?string $Code = null,
        public readonly ?string $Id = null,
        public readonly ?string $Message = null,
        public readonly ?array $Errors = null,
       ) {
       }

    public static function fromArray(array $data): static
       {
        return new static(
            Code: isset($data['Code']) ? (string) $data['Code'] : null,
            Id: isset($data['Id']) ? (string) $data['Id'] : null,
            Message: isset($data['Message']) ? (string) $data['Message'] : null,
            Errors: isset($data['Errors']) ? self::nestedAll($data['Errors'], ErrorDetailDTO::class) : null,
           );
       }

    public function toArray(): array
       {
        return array_filter([
              'Code' => $this->Code,
              'Id' => $this->Id,
              'Message' => $this->Message,
              'Errors' => $this->Errors !== null
               ? array_map(static fn (ErrorDetailDTO $e) => $e->toArray(), $this->Errors)
               : null,
           ], static fn ($value) => $value !== null);
       }
}
