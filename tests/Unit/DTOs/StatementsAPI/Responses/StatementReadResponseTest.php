<?php

declare(strict_types=1);

use App\DTOs\StatementsAPI\Responses\Statements\StatementReadResponseDTO;

/*
 * Round-trip + strict-casing assertions for StatementReadResponseDTO,
 * including the inline StatementFee / StatementAmount children.
 * Hermetic: no DB / HTTP.
 */

it('round-trips a fully populated StatementReadResponse envelope', function () {
    $payload = [
        'Data' => [
            'Statement' => [
                [
                    'AccountId' => '12345678',
                    'StatementId' => 'S-100',
                    'StatementReference' => 'REF-1',
                    'Type' => 'RegularPeriodic',
                    'StartDateTime' => '2025-09-01T00:00:00Z',
                    'EndDateTime' => '2025-09-30T23:59:59Z',
                    'CreationDateTime' => '2025-10-01T00:00:00Z',
                    'StatementDescription' => ['Monthly statement'],
                    'StatementFee' => [
                        [
                            'Description' => 'Service charge',
                            'CreditDebitIndicator' => 'Debit',
                            'Type' => 'ZA.ABSA.ServiceFee',
                            'Frequency' => 'ZA.ABSA.Quarterly',
                            'Amount' => [
                                'Amount' => '12.50',
                                'Currency' => 'ZAR',
                            ],
                        ],
                    ],
                    'StatementAmount' => [
                        [
                            'CreditDebitIndicator' => 'Credit',
                            'Type' => 'ZA.ABSA.ClosingBalance',
                            'Amount' => [
                                'Amount' => '1000.00',
                                'Currency' => 'ZAR',
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'Links' => [
            ['Rel' => 'next', 'Href' => 'https://api.absa.co.za/statements?page=2'],
        ],
        'Meta' => [
            ['Name' => 'page', 'Value' => 1],
        ],
    ];

    $dto = StatementReadResponseDTO::fromArray($payload);
    expect($dto->toArray())->toBe($payload);
});

it('exposes StatementFee and StatementAmount as nested DTOs with namespaced enums', function () {
    $dto = StatementReadResponseDTO::fromArray([
        'Data' => [
            'Statement' => [
                [
                    'Type' => 'Interim',
                    'StatementFee' => [
                        [
                            'Type' => 'ZA.ABSA.SwitchFee',
                            'Frequency' => 'ZA.ABSA.Weekly',
                            'CreditDebitIndicator' => 'Debit',
                        ],
                    ],
                    'StatementAmount' => [
                        [
                            'Type' => 'ZA.ABSA.TotalCredits',
                            'CreditDebitIndicator' => 'Credit',
                            'Amount' => ['Amount' => '42.00', 'Currency' => 'ZAR'],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $statement = $dto->Data->Statement[0];
    expect($statement->Type->value)->toBe('Interim');
    expect($statement->StatementFee[0]->Type->value)->toBe('ZA.ABSA.SwitchFee');
    expect($statement->StatementFee[0]->Frequency->value)->toBe('ZA.ABSA.Weekly');
    expect($statement->StatementAmount[0]->Type->value)->toBe('ZA.ABSA.TotalCredits');
    expect($statement->StatementAmount[0]->Amount->Currency)->toBe('ZAR');
});

it('preserves the exact OpenAPI key casing on the statement detail', function () {
    $array = StatementReadResponseDTO::fromArray([
        'Data' => [
            'Statement' => [
                [
                    'CreationDateTime' => '2025-10-01T00:00:00Z',
                    'StartDateTime' => '2025-09-01T00:00:00Z',
                ],
            ],
        ],
    ])->toArray();

    $detail = $array['Data']['Statement'][0];
    expect($detail)->toHaveKey('CreationDateTime');
    expect($detail)->toHaveKey('StartDateTime');
    expect($detail)->not->toHaveKey('creationDateTime');
    expect($detail)->not->toHaveKey('startDateTime');
});