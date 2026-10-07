<?php

declare(strict_types=1);

namespace App\Http\Requests\StatementsAPI;

use App\DTOs\StatementsAPI\Requests\GetAccountBalancesRequestDTO;

/**
 * Validates GET /api/v1/statements/accounts/{accountId}/balances.
 */
final class GetAccountBalancesRequest extends AbstractStatementsRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'accountId' => ['required', 'string', 'max:255'],
        ];
    }

    public function dto(): GetAccountBalancesRequestDTO
    {
        return new GetAccountBalancesRequestDTO(
            accountId: (string) $this->validated()['accountId'],
            headers: $this->absaHeaders(),
        );
    }
}
