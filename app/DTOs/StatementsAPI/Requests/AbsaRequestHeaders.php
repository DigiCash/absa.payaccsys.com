<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Requests;

use App\DTOs\StatementsAPI\Support\BaseDto;

/**
 * The cross-cutting ABSA headers present on every StatementsAPI operation:
 * Authorization + X-Absa-ClientInteractionId / X-Absa-Initiating-UserId /
 * X-Absa-Initiating-CompanyProfileId / X-Absa-Nonce.
 */
final readonly class AbsaRequestHeaders extends BaseDto
{
    public function __construct(
        public readonly ?string $authorization = null,
        public readonly ?string $clientInteractionId = null,
        public readonly ?string $initiatingUserId = null,
        public readonly ?string $initiatingCompanyProfileId = null,
        public readonly ?string $nonce = null,
    ) {
    }

    public static function fromArray(array $data): static
    {
        return new static(
            authorization: $data['authorization'] ?? null,
            clientInteractionId: $data['clientInteractionId'] ?? null,
            initiatingUserId: $data['initiatingUserId'] ?? null,
            initiatingCompanyProfileId: $data['initiatingCompanyProfileId'] ?? null,
            nonce: $data['nonce'] ?? null,
        );
    }

    /**
     * @return array<string, string> header name => value, nulls dropped
     */
    public function toArray(): array
    {
        return array_filter([
            'Authorization' => $this->authorization,
            'X-Absa-ClientInteractionId' => $this->clientInteractionId,
            'X-Absa-Initiating-UserId' => $this->initiatingUserId,
            'X-Absa-Initiating-CompanyProfileId' => $this->initiatingCompanyProfileId,
            'X-Absa-Nonce' => $this->nonce,
        ], static fn ($value) => $value !== null);
    }
}
