# API Integration — Statements & Transactions (active)

> Additional context for the active domain integration. Source spec is authoritative.
> Last updated: 2026-09-01.

## Source of truth
- **Spec:** `_StatementsAPI_/Statements_Facade_API_Swagger_20250910.yml` (OpenAPI 3.0.1) — **read-only**.
- **Derived plans:** `Planning/03_APIS/STATEMENTS/dto-plan.md` (approved 2026-08-26),
  `Planning/03_APIS/STATEMENTS/client-transport-plan.md` (M0–M4 done, M5/M6 pending).
- **Namespace:** `App\DTOs\StatementsAPI`. **Config seam:** `config('absa.statements')`.

## Endpoints → response envelopes
| Operation | Path | 200 schema |
|---|---|---|
| GetBalances | `GET /balances` | `BalancesReadResponse` |
| GetAccountBalances | `GET /accounts/{accountId}/balances` | `BalancesReadResponse` |
| GetStatementsAccountIdFromDateToDate | `GET /accounts/{accountId}/statements` | `StatementReadResponse` |
| GetStatementsAccountIdStatementId | `GET /accounts/{accountId}/statements/{statementId}` | `StatementReadResponse` |
| GetStatementsAccountIdStatementIdTransactions | `GET /accounts/{accountId}/statements/{statementId}/transactions` | `StatementTransactionsReadResponse` |
| GetIntraDayStatement | `GET /accounts/{accountId}/intraday-statement` | `IntraDayStatementReadResponse` |
| GetStatementFiles | `GET /accounts/{accountId}/intraday-statement/{fileName}/{fileType}` | `FileListResponse` |
| GetStatementFile / GetStatementFileData | same path | `File` (binary, not JSON) |
| GetHealth | `GET /health` | inline `{ status }` |
| All non-2xx | — | shared `Error` payload (400/401/403/404/405/406/429/500) |

## Key facts
- Every response envelope shares `{ Data, Links, Meta }`; `Data` holds one typed collection
  (`Balance[]`, `Statement[]`, `Transaction[]`, `IntraDayStatementDetail[]`, `File[]`).
- Spec has **no** `components/parameters` block — params reconstructed from `$ref` names.
- Cross-cutting request headers on every operation: `Authorization`,
  `X-Absa-ClientInteractionId`, `X-Absa-Initiating-UserId`,
  `X-Absa-Initiating-CompanyProfileId`, `X-Absa-Nonce`.
- `x-namespaced-enum` values (`ZA.ABSA.*`) → safe case names, raw string as backing value.
- `CurrencyCode` = value DTO (ISO-4217, 31 codes), NOT an enum (documented exception).
- `SupplementaryData` = empty placeholder DTO. Binary `File` handled outside the DTO layer.

## Transport status
- `StatementsApiClientInterface` (8 methods), `StatementsApiException`, `StatementsApiClientConfig` — done.
- `StatementsApiClient` — done through M4 (happy + error paths). **M5 (mTLS+retry) + M6 (sign-off) pending.**
- Auth currently: `Authorization: Bearer {apiKey}` (D1 open: Bearer vs token endpoint).
- mTLS `withOptions()` key set unresolved (D3: Guzzle `cert`/`ssl_key` vs Laravel-curl `curl_ssl_cert`/`curl_ssl_key`).

## Open questions
- **D1:** auth mechanism (Bearer `api_key` vs token endpoint).
- **D3:** exact `withOptions()` TLS key set.
- (D2 resolved: transport in `App\DTOs\StatementsAPI\Transport\`, test in `tests/Unit/Services/StatementsAPI/`.)

## Next
1. Resolve D1/D3 → implement M5 (mTLS + retry tests) → M6 sign-off.
2. Then start PayShap, then AVS (mirror this structure in new namespaces).
