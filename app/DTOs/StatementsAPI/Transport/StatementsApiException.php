<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Transport;

use App\DTOs\StatementsAPI\Errors\ErrorResponseDTO;

/**
 * Typed transport error for any non-2xx Statements API response.
 *
 * Carries the HTTP status, the decoded `ErrorResponse` body (when one is
 * present), and diagnostic context — the failing HTTP method/URL and a
 * truncated preview of the raw response body. The exception message is built
 * to be self-describing so that a gateway 4xx/5xx with an empty or non-JSON
 * body (e.g. `404` with no payload) still tells you exactly which call failed.
 */
final class StatementsApiException extends \RuntimeException
{
    public function __construct(
        public readonly int $status,
        public readonly ?ErrorResponseDTO $error = null,
        public readonly string $method = 'GET',
        public readonly string $url = '',
        public readonly ?string $body = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            message: $this->buildMessage($previous?->getMessage()),
            code: 0,
            previous: $previous,
        );
    }

    /**
     * Build a self-describing message from the available diagnostic context.
     */
    private function buildMessage(?string $previousMessage): string
    {
        $context = trim("{$this->method} {$this->url}");

        if ($context === '') {
            $context = 'ABSA Statements API request';
        }

        if ($this->status === 0) {
            return "{$context} failed to connect: ".($previousMessage ?? 'unknown connection error');
        }

        if ($this->error !== null && $this->error->Message !== null && $this->error->Message !== '') {
            return "{$context} failed with HTTP {$this->status}: {$this->error->Message}";
        }

        if ($this->body !== null && $this->body !== '') {
            return "{$context} failed with HTTP {$this->status} and no decodable ABSA error payload. Raw response: {$this->body}";
        }

        return "{$context} failed with HTTP {$this->status} and an empty response body.";
    }
}
