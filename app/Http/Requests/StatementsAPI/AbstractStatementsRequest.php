<?php

declare(strict_types=1);

namespace App\Http\Requests\StatementsAPI;

use App\DTOs\StatementsAPI\Requests\AbsaRequestHeaders;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared base for every inbound Statements facade Form Request.
 *
 * Owns the two cross-cutting concerns of the inbound layer:
 *
 *  1. Authorisation — the route itself is Sanctum-protected (`auth:sanctum`),
 *     so the request is always allowed once it is resolvable.
 *  2. ABSA cross-cutting headers — the caller-controlled `X-Absa-*` headers
 *     are forwarded to the outbound client; `Authorization` is deliberately
 *     NOT forwarded because the transport always emits the resolved bearer
 *     token via its `apiKey` seam (ADR-001).
 */
abstract class AbstractStatementsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Include the route parameters (`accountId`, `statementId`) in the data
     * that gets validated. Laravel 13's `FormRequest::validationData()`
     * returns only the request input, so path segments must be merged in here
     * for `required` rules (and the resulting `validated()` payload) to see them.
     *
     * @return array<string, mixed>
     */
    public function validationData(): array
    {
        return array_merge(
            $this->all(),
            $this->route()?->parameters() ?? [],
        );
    }

    /**
     * Forward the ABSA cross-cutting headers supplied by the inbound caller,
     * or `null` when none are present.
     */
    protected function absaHeaders(): ?AbsaRequestHeaders
    {
        $values = array_filter([
            'clientInteractionId' => $this->header('X-Absa-ClientInteractionId'),
            'initiatingUserId' => $this->header('X-Absa-Initiating-UserId'),
            'initiatingCompanyProfileId' => $this->header('X-Absa-Initiating-CompanyProfileId'),
            'nonce' => $this->header('X-Absa-Nonce'),
        ], static fn (?string $value): bool => $value !== null && $value !== '');

        return $values === [] ? null : new AbsaRequestHeaders(...$values);
    }
}
