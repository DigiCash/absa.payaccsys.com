<?php

declare(strict_types=1);

namespace App\Http\Requests\StatementsAPI;

use App\DTOs\StatementsAPI\Requests\GetStatementRequestDTO;

/**
 * Validates GET /api/v1/statements/accounts/{accountId}/statements/{statementId}.
 */
final class GetStatementRequest extends AbstractStatementsRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'accountId' => ['required', 'string', 'max:255'],
            'statementId' => ['required', 'string', 'max:255'],
        ];
    }

    public function dto(): GetStatementRequestDTO
    {
        $validated = $this->validated();

        return new GetStatementRequestDTO(
            accountId: (string) $validated['accountId'],
            statementId: (string) $validated['statementId'],
            headers: $this->absaHeaders(),
        );
    }
}
