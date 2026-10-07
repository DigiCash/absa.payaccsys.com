<?php

declare(strict_types=1);

namespace App\Services\StatementsAPI\Contracts;

/**
 * The single seam by which the transport layer obtains a usable bearer
 * token for outbound ABSA Statements API calls.
 *
 * Implementations own credential acquisition (OAuth2 Client Credentials with
 * caching / auto-refresh, or the static-key fallback — ADR-001) and expose
 * only a resolved token string, so the transport never learns where the
 * token came from.
 */
interface OAuth2TokenManagerInterface
{
    /**
     * Return a valid bearer token, acquiring or refreshing one as needed.
     */
    public function getValidToken(): string;
}
