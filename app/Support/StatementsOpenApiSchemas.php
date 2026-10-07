<?php

declare(strict_types=1);

namespace App\Support;

/**
 * OpenAPI response-schema enrichment for the inbound ABSA Statements facade.
 *
 * Scramble auto-generates the structural spec (paths, parameters, security,
 * errors) but emits a generic `items: {}` for the controller 200 bodies
 * because they are serialised through `->json($dto->toArray())`. This helper
 * supplies the verified response schemas for those operations, wired via
 * `Scramble::afterOpenApiGenerated()` in {@see App\Providers\AppServiceProvider}.
 *
 * Schemas are derived from the Statements DTO layer
 * (`app/DTOs/StatementsAPI/Responses/*` and `Models/*`) — no field or value is
 * invented. Deep nested model objects (amounts, fees, bank transaction codes,
 * …) are documented as objects referencing the ABSA source spec; enumerate
 * their exact properties from the DTO classes before extending this map.
 */
final readonly class StatementsOpenApiSchemas
{
    /** OpenAPI schema for `GET /v1/statements/health`. */
    public static function healthSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'status' => ['type' => ['string', 'null']],
            ],
            'title' => 'Health',
        ];
    }

    /** Component schemas for every response root + nested object. */
    public static function componentSchemas(): array
    {
        return [
            'Health' => self::healthSchema(),
            'BalancesResponse' => self::envelopeSchema('BalancesData'),
            'BalancesData' => [
                'type' => 'object',
                'properties' => [
                    'Balance' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/BalancesDetail'], 'nullable' => true],
                ],
            ],
            'StatementResponse' => self::envelopeSchema('StatementData'),
            'StatementData' => [
                'type' => 'object',
                'properties' => [
                    'Statement' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/StatementDetail'], 'nullable' => true],
                ],
            ],
            'TransactionResponse' => self::envelopeSchema('TransactionData'),
            'TransactionData' => [
                'type' => 'object',
                'properties' => [
                    'Transaction' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/TransactionDetail'], 'nullable' => true],
                ],
            ],
            'Link' => [
                'type' => 'object',
                'properties' => [
                    'Rel' => ['type' => ['string', 'null']],
                    'Href' => ['type' => ['string', 'null']],
                ],
                'title' => 'Link',
            ],
            'Metadatum' => [
                'type' => 'object',
                'properties' => [
                    'Name' => ['type' => ['string', 'null']],
                    'Value' => ['description' => 'Metadata value (any type).'],
                ],
                'title' => 'Metadatum',
            ],
            'BalancesDetail' => [
                'type' => 'object',
                'properties' => [
                    'AccountId' => ['type' => ['string', 'null']],
                    'Amount' => self::nestedRef('CurrencyAmountSubType'),
                    'LocalAmount' => self::nestedRef('CurrencyAmountSubType'),
                    'TotalAmount' => self::nestedRef('CurrencyAndAmount'),
                    'CreditDebitIndicator' => ['type' => ['string', 'null'], 'description' => 'String-backed enum; see ABSA source spec / App\\DTOs\\StatementsAPI\\Enums\\CreditDebitCode.'],
                    'Type' => ['type' => ['string', 'null'], 'description' => 'String-backed enum; see ABSA source spec / App\\DTOs\\StatementsAPI\\Enums\\ExternalBalanceType.'],
                    'DateTime' => ['type' => ['string', 'null'], 'format' => 'date-time'],
                    'CreditLine' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/CreditLineTypeDetail'], 'nullable' => true],
                ],
                'title' => 'BalancesDetail',
            ],
            'StatementDetail' => [
                'type' => 'object',
                'properties' => [
                    'AccountId' => ['type' => ['string', 'null']],
                    'StatementId' => ['type' => ['string', 'null']],
                    'StatementReference' => ['type' => ['string', 'null']],
                    'Type' => ['type' => ['string', 'null'], 'description' => 'String-backed enum; see App\\DTOs\\StatementsAPI\\Enums\\StatementTypeCode.'],
                    'StartDateTime' => ['type' => ['string', 'null'], 'format' => 'date-time'],
                    'EndDateTime' => ['type' => ['string', 'null'], 'format' => 'date-time'],
                    'CreationDateTime' => ['type' => ['string', 'null'], 'format' => 'date-time'],
                    'StatementDescription' => ['type' => 'array', 'items' => ['type' => 'string'], 'nullable' => true],
                    'StatementFee' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/StatementFee'], 'nullable' => true],
                    'StatementAmount' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/StatementAmount'], 'nullable' => true],
                ],
                'title' => 'StatementDetail',
            ],
            'TransactionDetail' => [
                'type' => 'object',
                'properties' => [
                    'AccountId' => ['type' => ['string', 'null']],
                    'TransactionId' => ['type' => ['string', 'null']],
                    'TransactionReference' => ['type' => ['string', 'null']],
                    'StatementReference' => ['type' => 'array', 'items' => ['type' => 'string'], 'nullable' => true],
                    'CreditDebitIndicator' => ['type' => ['string', 'null'], 'description' => 'String-backed enum; see App\\DTOs\\StatementsAPI\\Enums\\CreditDebitCode.'],
                    'Status' => ['type' => ['string', 'null'], 'description' => 'String-backed enum; see App\\DTOs\\StatementsAPI\\Enums\\EntryStatusCode.'],
                    'BookingDateTime' => ['type' => ['string', 'null'], 'format' => 'date-time'],
                    'ValueDate' => ['type' => ['string', 'null'], 'format' => 'date-time'],
                    'TransactionInformation' => ['type' => ['string', 'null']],
                    'Amount' => self::nestedRef('CurrencyAndAmount'),
                    'ChargeAmount' => self::nestedRef('CurrencyAndAmount'),
                    'BankTransactionCode' => self::nestedRef('BankTransactionCodeStructure'),
                    'ProprietaryBankTransactionCode' => self::nestedRef('ProprietaryBankTransactionCodeStructure'),
                    'Balance' => self::nestedRef('TransactionCashBalance'),
                    'SupplementaryData' => self::nestedRef('SupplementaryData'),
                ],
                'title' => 'TransactionDetail',
            ],
            // Deep nested ABSA models — exact properties live in the DTO
            // classes; enrich here (from app/DTOs/StatementsAPI/Models/*) when
            // consumers need the full nesting.
            'CurrencyAmountSubType' => self::nestedPlaceholder('CurrencyAmountSubType'),
            'CurrencyAndAmount' => self::nestedPlaceholder('CurrencyAndAmount'),
            'CreditLineTypeDetail' => self::nestedPlaceholder('CreditLineTypeDetail'),
            'StatementFee' => self::nestedPlaceholder('StatementFee'),
            'StatementAmount' => self::nestedPlaceholder('StatementAmount'),
            'BankTransactionCodeStructure' => self::nestedPlaceholder('BankTransactionCodeStructure'),
            'ProprietaryBankTransactionCodeStructure' => self::nestedPlaceholder('ProprietaryBankTransactionCodeStructure'),
            'TransactionCashBalance' => self::nestedPlaceholder('TransactionCashBalance'),
            'SupplementaryData' => self::nestedPlaceholder('SupplementaryData'),
        ];
    }

    /**
     * Map an operationId to its response root component schema.
     */
    public static function schemaRefForOperation(string $operationId): ?array
    {
        $schema = null;

        match ($operationId) {
            'health.index' => $schema = 'Health',
            'balances.index', 'balances.show' => $schema = 'BalancesResponse',
            'statements.all', 'statements.index', 'statements.show' => $schema = 'StatementResponse',
            'statementTransactions.index', 'statementTransactions.intraday' => $schema = 'TransactionResponse',
            default => $schema = null,
        };

        if ($schema === null) {
            return null;
        }

        return ['$ref' => '#/components/schemas/'.$schema];
    }

    /**
     * Shared `{ Data, Links, Meta }` envelope schema.
     */
    private static function envelopeSchema(string $dataSchema): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'Data' => ['$ref' => '#/components/schemas/'.$dataSchema],
                'Links' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/Link'], 'nullable' => true],
                'Meta' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/Metadatum'], 'nullable' => true],
            ],
        ];
    }

    /** `$ref` to a nested component schema (nullable). */
    private static function nestedRef(string $schema): array
    {
        return ['$ref' => '#/components/schemas/'.$schema, 'nullable' => true];
    }

    /** Honest placeholder for a deep nested ABSA model not yet enumerated. */
    private static function nestedPlaceholder(string $name): array
    {
        return [
            'type' => 'object',
            'description' => "Exact properties per the ABSA Statements API source spec — see app/DTOs/StatementsAPI/Models/{$name}DTO.php.",
            'additionalProperties' => [],
        ];
    }
}
