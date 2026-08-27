<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Errors;

use App\DTOs\StatementsAPI\Support\BaseDto;

/**
 * Error — a single detailed error item nested inside ErrorResponse.Errors.
 */
final readonly class ErrorDetailDTO extends BaseDto
{
    public function __construct(
        public readonly ?string $ErrorCode = null,
        public readonly ?string $Message = null,
        public readonly ?string $Path = null,
        public readonly ?string $Url = null,
       ) {
       }

    public static function fromArray(array $data): static
       {
        return new static(
            ErrorCode: isset($data['ErrorCode']) ? (string) $data['ErrorCode'] : null,
            Message: isset($data['Message']) ? (string) $data['Message'] : null,
            Path: isset($data['Path']) ? (string) $data['Path'] : null,
            Url: isset($data['Url']) ? (string) $data['Url'] : null,
           );
       }

    public function toArray(): array
       {
        return array_filter([
              'ErrorCode' => $this->ErrorCode,
              'Message' => $this->Message,
              'Path' => $this->Path,
              'Url' => $this->Url,
           ], static fn ($value) => $value !== null);
       }
}
