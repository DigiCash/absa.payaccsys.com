<?php

declare(strict_types=1);

use App\DTOs\StatementsAPI\Responses\StatementTransactions\TransactionReadResponseDTO;

/*
 * Round-trip + strict-casing assertions for TransactionReadResponseDTO,
 * which is also the envelope reused by the intraday-statement operation.
 * Hermetic: no DB / HTTP.
 */

it('round-trips a fully populated TransactionReadResponse envelope', function () {
    $payload = [
        'Data' => [
             'Transaction' => [
                 [
                     'AccountId' => '12345678',
                     'TransactionId' => 'T-1',
                     'TransactionReference' => 'TRX-1',
                     'StatementReference' => ['REF-1'],
                     'CreditDebitIndicator' => 'Debit',
                     'Status' => 'Booked',
                     'BookingDateTime' => '2025-09-05T10:00:00Z',
                     'ValueDate' => '2025-09-05',
                     'TransactionInformation' => 'ATM withdrawal',
                     'Amount' => [
                         'Amount' => '50.00',
                         'Currency' => 'ZAR',
                     ],
                     'ChargeAmount' => [
                         'Amount' => '1.50',
                         'Currency' => 'ZAR',
                     ],
                     'BankTransactionCode' => [
                         'Code' => '01',
                         'SubCode' => '02',
                     ],
                     'ProprietaryBankTransactionCode' => [
                         'Code' => '99',
                         'Issuer' => 'ABSA',
                     ],
                     'Balance' => [
                         'CreditDebitIndicator' => 'Debit',
                         'Type' => 'ClosingBooked',
                         'Amount' => [
                             'Amount' => '200.00',
                             'Currency' => 'ZAR',
                         ],
                     ],
                     'SupplementaryData' => [],
                 ],
             ],
         ],
         'Links' => [
             ['Rel' => 'self', 'Href' => 'https://api.absa.co.za/transactions'],
         ],
         'Meta' => [
             ['Name' => 'totalCount', 'Value' => 1],
         ],
     ];

    $dto = TransactionReadResponseDTO::fromArray($payload);
    expect($dto->toArray())->toBe($payload);
});

it('exposes the transaction and its nested structures as DTOs', function () {
    $dto = TransactionReadResponseDTO::fromArray([
         'Data' => [
             'Transaction' => [
                 [
                     'Status' => 'Pending',
                     'CreditDebitIndicator' => 'Credit',
                     'BankTransactionCode' => ['Code' => '10', 'SubCode' => '20'],
                     'Balance' => [
                         'Type' => 'ClosingBooked',
                         'Amount' => ['Amount' => '10.00', 'Currency' => 'ZAR'],
                     ],
                 ],
             ],
         ],
     ]);

    $transaction = $dto->Data->Transaction[0];
    expect($transaction->Status->value)->toBe('Pending');
    expect($transaction->CreditDebitIndicator->value)->toBe('Credit');
    expect($transaction->BankTransactionCode->Code)->toBe('10');
    expect($transaction->Balance->Type->value)->toBe('ClosingBooked');
    expect($transaction->Balance->Amount->Currency)->toBe('ZAR');
});

it('serialises an empty SupplementaryData placeholder as an empty object', function () {
    $array = TransactionReadResponseDTO::fromArray([
         'Data' => [
             'Transaction' => [
                 [
                     'TransactionId' => 'T-1',
                     'SupplementaryData' => [],
                 ],
             ],
         ],
     ])->toArray();

     expect($array['Data']['Transaction'][0]['SupplementaryData'])->toBe([]);
});

it('preserves the exact OpenAPI key casing on the transaction detail', function () {
    $array = TransactionReadResponseDTO::fromArray([
         'Data' => [
             'Transaction' => [
                 [
                     'CreditDebitIndicator' => 'Debit',
                     'BookingDateTime' => '2025-09-05T10:00:00Z',
                     'ProprietaryBankTransactionCode' => ['Code' => '99', 'Issuer' => 'ABSA'],
                 ],
             ],
         ],
     ])->toArray();

    $transaction = $array['Data']['Transaction'][0];
    expect($transaction)->toHaveKey('CreditDebitIndicator');
    expect($transaction)->toHaveKey('ProprietaryBankTransactionCode');
    expect($transaction)->toHaveKey('BookingDateTime');
    expect($transaction)->not->toHaveKey('creditDebitIndicator');
    expect($transaction)->not->toHaveKey('proprietaryBankTransactionCode');
});