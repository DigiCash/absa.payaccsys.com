<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Transport;

use App\DTOs\StatementsAPI\Errors\ErrorResponseDTO;

/**
 * Typed transport error raised when the OAuth2 token endpoint fails to
 * return a usable bearer token (non-2xx response or undecodable body).
 *
 * Carries the HTTP status and the decoded `ErrorResponse` body when one is
 * present, mirroring {@see StatementsApiException}.
 */
final class TokenAcquisitionException extends \RuntimeException
{
    public function __construct(
        public readonly int $status,
        public readonly ?ErrorResponseDTO $error = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            message: $error?->Message ?? "Token acquisition failed ({$status})",
            code: 0,
            previous: $previous,
        );
    }
}
