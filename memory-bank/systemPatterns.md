# System Patterns — ABSA API Hub

> Architecture, key technical decisions, design patterns, component relationships, critical paths.
> Last updated: 2026-10-06.

## 1. High-level architecture
```
Internal consumer
   │  (Sanctum token via POST /v1/login)
   ▼
Hub routes (routes/api.php, /v1)
   ▼
Inbound facade controller + StatementService
   ▼
StatementsApiClientInterface  ──►  StatementsApiClient (Illuminate\Http\Client)
   ▲  (typed *RequestDTO)            │  outbound GET: path + query + ABSA headers + mTLS + Bearer
   │                                 ▼
   │                          ABSA Statements API (external, mTLS)
   │                                 │  200 JSON / non-2xx
   ▼                                 ▼
*ResponseDTO  ◄── decode ──  StatementsApiException (non-2xx, carries ErrorResponseDTO)
```
Callers depend on the **interface**, never on Guzzle/HttpClient. Transport detail is isolated to
`App\DTOs\StatementsAPI\Transport\`. The application/domain layer is **complete**: `OAuth2TokenManager`,
`StatementService`, `ApiAuditLogger` and the inbound facade controllers are all implemented + tested
(2026-09-03 / 2026-09-04).

## 2. Domain isolation (anti-premature-abstraction)
- Each ABSA capability lives in its own namespace: `App\DTOs\StatementsAPI\` (active),
  future `App\DTOs\PayShap\`, `App\DTOs\AVS\`.
- **No shared classes across unbuilt domains** until concrete duplication is proven.
- `config/absa.php` already reserves `payshap`/`avs` placeholders for this.

## 3. DTO layer pattern (`App\DTOs\StatementsAPI\`)
- **Support:** `Arrayable` (interface: `toArray(): array`), `FromArray` (contract: `fromArray(array): self`),
  `BaseDto` (abstract helpers: case-sensitive keys, nested hydration).
- **Enums:** native string-backed (e.g. `CreditDebitCode`, `ExternalBalanceType`, `StatementTypeCode`).
- **Requests:** `*RequestDTO` + `Requests/Query/` (`PaginationQuery`, `StatementDateRangeQuery`) +
  `AbsaRequestHeaders` (5 cross-cutting ABSA headers + `Authorization`).
- **Responses:** per-endpoint `*ReadResponseDTO` → `*ReadDataDTO` → model DTOs.
- **Errors:** `ErrorResponseDTO` + `ErrorDetailDTO` (one model for all 4xx/5xx).
- Dependency direction is strictly downward (response → data → model → enum); **no cycles**.

## 4. Transport layer pattern
- `StatementsApiClientInterface` — 8-method contract (fixed path).
- `StatementsApiClientConfig` — immutable value object built from `config('absa.statements')`
  (`fromConfig()`; explicit-array override for hermetic tests).
- `StatementsApiException` — typed transport error (`status` + decoded `ErrorResponseDTO` +
  `method`/`url`/`body` diagnostics; self-describing message for empty/non-JSON 4xx/5xx).
- `StatementsApiClient` — concrete impl: `Http::baseUrl`/`timeout`/`withOptions` (mTLS) +
  `Authorization: Bearer {token}` read **dynamically at call time** from the runtime config slot
  `absa.statements.api_key` + non-2xx → `StatementsApiException`.

## 5. Dual-database design
- **Application DB:** PostgreSQL — MCP `postgres-switch-main`, db `absa_api` (audit logs).
- **Operational/audit logging DB:** MySQL 8.0.42 — MCP `mysql-monolith`, db `logging`, table `logs`.
- Logging via `payaccsys/laravel-db-logger` (private VCS) + `app/Support/DatabaseLogProxy.php`
  (forwards to `Log::channel('log_stack')`, injects true caller file/line + logger `name`) and
  `app/Traits/InteractsWithDatabaseLog.php` (`__get('logDb')` factory, default name `fingo-application`).
- **MCP DB access is READ-ONLY** unless a developer explicitly authorises a write.

## 6. Auth & config
- Internal auth: **Laravel Sanctum** (`routes/api.php`: `POST /v1/login` issues token,
  `GET /v1/user` behind `auth:sanctum`).
- ABSA config seam: `config('absa.statements')` → `base_url`, `client_id`,
  `passphrase`, `cert_path`, `retry_attempts` (3), `retry_delay_ms` (250), `oauth_token_url`,
  `scope` (default `bifrost-gateway`), `username`, `password`, `token_cache_key`, `token_ttl_buffer`.
  (No `api_key` env/credential — `absa.statements.api_key` is a **runtime** slot only.)
- `environment` = `ABSA_ENV` (default `sandbox`); base_url no longer branches on it — it is a
  direct env/default (`https://api.absa.africa/cheque-statements`).
- **Outbound auth:** OAuth2 **Resource Owner Password grant** (`grant_type=password`, POST body
  `client_id`, `scope`, `username`, `password`, `application/x-www-form-urlencoded`) to
  `https://mtls.auth.absaaccess.africa/connect/token` over mTLS (p12 cert + passphrase). The same
  p12 `cert` option is applied to Statements API calls via `sslOptions()`.

## 7. Critical implementation paths
- **Adding a new endpoint:** spec → `*RequestDTO`/`*ResponseDTO` (+ enums/models) → add method to
  `StatementsApiClientInterface` → implement in `StatementsApiClient` → hermetic `Http::fake` test.
- **Adding a new domain (PayShap/AVS):** mirror the Statements structure in a new namespace;
  do not touch Statements code.

## 8. Key technical decisions (recorded)
- Native PHP DTOs/enums (no DTO library) — zero new dependencies.
- Transport kept inside the domain namespace (`App\DTOs\StatementsAPI\Transport\`), not `app/Services`.
- One `ErrorResponseDTO` for all error codes; HTTP code mapped at caller/handler layer.
- `CurrencyCode` = value DTO, not enum (ISO-4217 external list).
- `SupplementaryData` modelled as empty placeholder DTO to preserve field contract.
- Binary `File`/file-download endpoints are NOT modelled as JSON (handled outside DTO layer).

## 9. Application / domain layer (complete)
The transport layer (M0–M6) and the application layer (App-M1 → App-M4) are complete and tested.
Components:

- **`OAuth2TokenManager` — ✅ DONE (2026-09-03, password-grant rework 2026-10-06)** — `App\Services\StatementsAPI\OAuth2TokenManager`
  (concrete) + `Contracts\OAuth2TokenManagerInterface` (`getValidToken(): string`). Resolves the ABSA
  OAuth2 **Resource Owner Password** token (`grant_type=password` over mTLS, cached in Cache for
  `expires_in - token_ttl_buffer`, floored at 0); non-2xx/undecodable/missing-credentials → `TokenAcquisitionException`.
  **No static-key fallback.** Resolves **D1 (Auth)** via **ADR-001**.
- **`ApiAuditLogger`** — ✅ DONE (2026-09-04) — writes audit/trace records to the MySQL logging DB
  (`mysql_fingo_logs` / `logging.logs`) for inbound facade calls and outbound ABSA calls.
- **`StatementService`** — ✅ DONE (2026-09-04) — orchestrates: resolve token → build `*RequestDTO` → call
  `StatementsApiClientInterface` → decode `*ResponseDTO` / surface `StatementsApiException`.
- **Inbound facade controllers** (`App\Http\Controllers\StatementsAPI\`) — thin HTTP adapters over
  `StatementService`, exposed under `/api/v1/statements/*` via `routes/statements.php`.

Constraints carried into the application layer:
- TLS/mTLS posture is fixed by **ADR-003** (resolves **D3 (TLS)**); no new transport decisions.
- Controllers depend on `StatementService`, which depends on the `StatementsApiClientInterface` —
  never on Guzzle/HttpClient directly.
- Audit logging must be hermetic-friendly: `ApiAuditLogger` is injected via an interface so unit
  tests can substitute a no-op double (zero DB/network overhead under `tests/Unit/`).
