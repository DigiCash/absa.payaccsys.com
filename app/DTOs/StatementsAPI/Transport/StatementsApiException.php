<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Transport;

use App\DTOs\StatementsAPI\Errors\ErrorResponseDTO;

/**
 * Typed transport error for any non-2xx Statements API response.
 * Carries the HTTP status and the decoded `ErrorResponse` body.
 */
final class StatementsApiException extends \RuntimeException
{
    public function __construct(
        public readonly int $status,
        public readonly ?ErrorResponseDTO $error = null,
        ?\Throwable $previous = null,
      ) {
        parent::__construct(
            message: $error?->Message ?? "Statements API error {$status}",
            code: 0,
            previous: $previous,
          );
      }
}
