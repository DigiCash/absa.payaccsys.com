<?php

declare(strict_types=1);

use App\DTOs\StatementsAPI\Errors\ErrorResponseDTO;

/*
 * Round-trip + strict-casing assertions for the single shared error payload
 * (400/401/403/404/405/406/429/500). Hermetic: no DB / HTTP.
 */

it('round-trips a populated ErrorResponse with nested error details', function () {
    $payload = [
         'Code' => 'VALIDATION_ERROR',
         'Id' => 'abc-123',
         'Message' => 'Invalid request body',
         'Errors' => [
             [
                 'ErrorCode' => 'INVALID_PARAMETER',
                 'Message' => 'Field "accountId" is required',
                 'Path' => '$.accountId',
                 'Url' => 'https://api.absa.co.za/errors/INVALID_PARAMETER',
             ],
         ],
     ];

     $dto = ErrorResponseDTO::fromArray($payload);
    expect($dto->toArray())->toBe($payload);
});

it('exposes nested error details as DTOs', function () {
    $dto = ErrorResponseDTO::fromArray([
         'Code' => 'NOT_FOUND',
         'Errors' => [
             ['ErrorCode' => 'RESOURCE_MISSING', 'Path' => '$.statementId'],
         ],
     ]);

    expect($dto->Code)->toBe('NOT_FOUND');
    expect($dto->Errors[0]->ErrorCode)->toBe('RESOURCE_MISSING');
    expect($dto->Errors[0]->Path)->toBe('$.statementId');
    expect($dto->Errors[0]->Url)->toBeNull();
});

it('preserves the exact OpenAPI key casing on the error payload', function () {
    $array = ErrorResponseDTO::fromArray([
         'Code' => 'X',
         'Id' => 'Y',
         'Message' => 'Z',
         'Errors' => [['ErrorCode' => 'E', 'Message' => 'M', 'Path' => 'P', 'Url' => 'U']],
     ])->toArray();

    expect($array)->toHaveKeys(['Code', 'Id', 'Message', 'Errors']);
    expect($array['Errors'][0])->toHaveKeys(['ErrorCode', 'Message', 'Path', 'Url']);
    expect($array)->not->toHaveKey('code');
    expect($array['Errors'][0])->not->toHaveKey('errorCode');
});

it('drops null error fields that were never supplied', function () {
    $array = ErrorResponseDTO::fromArray([
         'Errors' => [['ErrorCode' => 'E']],
     ])->toArray();

    expect($array)->not->toHaveKey('Code');
    expect($array['Errors'][0])->not->toHaveKey('Message');
});