<?php

declare(strict_types=1);

use App\DTOs\StatementsAPI\Responses\Balances\BalancesReadResponseDTO;

/*
 * Round-trip + strict-casing assertions for BalancesReadResponseDTO.
 * Hermetic: no DB / HTTP.
 */

it('round-trips a fully populated BalancesReadResponse envelope', function () {
     $payload = [
         'Data' => [
             'Balance' => [
                 [
                     'AccountId' => '12345678',
                     'Amount' => [
                         'Amount' => '100.00',
                         'Currency' => 'ZAR',
                         'SubType' => 'BCUR',
                     ],
                     'LocalAmount' => [
                         'Amount' => '100.00',
                         'Currency' => 'ZAR',
                     ],
                     'TotalAmount' => [
                         'Amount' => '100.00',
                         'Currency' => 'ZAR',
                     ],
                     'CreditDebitIndicator' => 'Credit',
                     'Type' => 'CLAV',
                     'DateTime' => '2025-09-10T00:00:00Z',
                     'CreditLine' => [
                         [
                             'Included' => true,
                             'Amount' => [
                                 'Amount' => '500.00',
                                 'Currency' => 'ZAR',
                             ],
                             'Type' => 'Available',
                         ],
                     ],
                 ],
             ],
         ],
         'Links' => [
             ['Rel' => 'self', 'Href' => 'https://api.absa.co.za/balances'],
         ],
         'Meta' => [
             ['Name' => 'totalCount', 'Value' => 1],
         ],
     ];

     $dto = BalancesReadResponseDTO::fromArray($payload);
    expect($dto->toArray())->toBe($payload);
});

it('exposes nested typed models, enums and the credit line as DTOs', function () {
     $dto = BalancesReadResponseDTO::fromArray([
         'Data' => [
             'Balance' => [
                 [
                     'AccountId' => '1',
                     'CreditDebitIndicator' => 'Debit',
                     'Type' => 'ITAV',
                     'CreditLine' => [
                         [
                             'Included' => false,
                             'Amount' => ['Amount' => '25.00', 'Currency' => 'ZAR'],
                             'Type' => 'Pre-Agreed',
                         ],
                     ],
                 ],
             ],
         ],
     ]);

     $balance = $dto->Data->Balance[0];
    expect($balance->AccountId)->toBe('1');
    expect($balance->CreditDebitIndicator->value)->toBe('Debit');
    expect($balance->Type->value)->toBe('ITAV');
    expect($balance->CreditLine[0]->Included)->toBeFalse();
    expect($balance->CreditLine[0]->Type->value)->toBe('Pre-Agreed');
    expect($balance->Amount)->toBeNull(); // not supplied
});

it('serialises with the exact OpenAPI envelope casing', function () {
     $dto = BalancesReadResponseDTO::fromArray([
         'Data' => ['Balance' => [['AccountId' => '1', 'CreditDebitIndicator' => 'Debit']]],
     ]);

     $array = $dto->toArray();
    expect(array_keys($array))->toBe(['Data']);
    expect($array['Data'])->toHaveKey('Balance');
    expect($array['Data']['Balance'][0])->toHaveKey('CreditDebitIndicator');
    expect($array['Data']['Balance'][0])->not->toHaveKey('creditDebitIndicator');
});

it('drops the null Data/Links/Meta that were never supplied', function () {
     $array = BalancesReadResponseDTO::fromArray([
         'Data' => ['Balance' => [['AccountId' => '1', 'CreditDebitIndicator' => 'Debit']]],
     ])->toArray();

    expect($array)->not->toHaveKey('Links');
    expect($array)->not->toHaveKey('Meta');
});