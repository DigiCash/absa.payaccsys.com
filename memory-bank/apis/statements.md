# API Integration — Statements & Transactions (active)

> Additional context for the active domain integration. Source spec is authoritative.
> Last updated: 2026-10-06.

## Source of truth
- **Spec:** `_StatementsAPI_/Statements_Facade_API_Swagger_20250910.yml` (OpenAPI 3.0.1) — **read-only**.
- **Derived plans:** `Planning/03_APIS/STATEMENTS/dto-plan.md` (approved 2026-08-26),
  `Planning/03_APIS/STATEMENTS/client-transport-plan.md` (M0–M6 done, signed off 2026-09-01).
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

## Transport status — COMPLETE (M0–M6, signed off 2026-09-01)
- `StatementsApiClientInterface` (8 methods), `StatementsApiException`, `StatementsApiClientConfig` — done.
- `StatementsApiClient` — done: happy + error paths, mTLS `sslOptions()` mapping + retry-on-5xx/429 (M5).
- Auth (D1 resolved, ADR-001; **grant updated 2026-10-06**): OAuth 2.0 **Resource Owner Password**
  via `OAuth2TokenManager` — **no static key**; the bearer token reaches the transport via
  `StatementService` → runtime config slot `absa.statements.api_key` → `withAuthorization()`.
  Token endpoint: `https://mtls.auth.absaaccess.africa/connect/token` (mTLS).
  Statements base: `https://api.absa.africa/cheque-statements/v1.0` (version prefix required —
  without `/v1.0` the gateway 404s).
- mTLS (D3 resolved, ADR-003; **p12-only since 2026-10-06**): single `.p12` cert →
  Guzzle `cert` (no `ssl_key`).

## Application layer — COMPLETE (2026-09-03 / 2026-09-04)
- `OAuth2TokenManager`, `ApiAuditLogger` + `AuditLogSanitizer` + queued `RecordApiAuditLog`,
  `StatementService`, inbound facade controllers + Form Requests, `routes/statements.php`
  (Sanctum-protected `api/v1/statements/*`). All covered by hermetic Pest tests.

## Open questions
- (D1 auth, D2 structure, D3 TLS — all resolved; D2: transport in `App\DTOs\StatementsAPI\Transport\`,
  test in `tests/Unit/Services/StatementsAPI/`.)
- No open transport/application questions for Statements.

## Next
1. Harden/extend Statements as needed; keep the Pest suite green (full suite **116/116,
   623 assertions** as of 2026-10-06; Statements suite 64/64).
2. Then start PayShap, then AVS (mirror this structure in new namespaces).
