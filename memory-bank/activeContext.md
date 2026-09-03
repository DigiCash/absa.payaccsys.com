# Active Context — ABSA API Hub

> Current focus, recent changes, next steps, open decisions. Update after significant changes.
> Last updated: 2026-09-03.

## Current focus
**Statements API — Application & Domain layer.** The DTO + transport layers are **100% complete and
signed off** (M0–M6 done 2026-09-01). **App-M1 (`OAuth2TokenManager`) is now IMPLEMENTED + tested**
(2026-09-03): full Pest suite **79/79, 460 assertions**; `php -l` + Pint clean. The remaining
application milestones (App-M2 → App-M4) are still a DRAFT blueprint awaiting approval.
- **Transport layer M0–M6 — DONE** (2026-09-01): `StatementsApiClient` (concrete, `Http::` facade),
  mTLS `sslOptions()` mapping + retry-on-5xx/429 (M5), sign-off (M6).
- **App-M1 — `OAuth2TokenManager` — DONE** (2026-09-03): concrete `App\Services\StatementsAPI\OAuth2TokenManager`
  + `Contracts\OAuth2TokenManagerInterface` (`getValidToken(): string`), `Responses/OAuth/OAuthTokenResponseDTO`,
  `Transport/TokenAcquisitionException`. OAuth2 Client Credentials grant + Cache caching + static `api_key`
  fallback (ADR-001). 6 hermetic tests (`OAuth2TokenManagerTest`, 22 assertions).
- **Application layer App-M2 → App-M4 — DRAFT** (2026-09-02): `ApiAuditLogger` middleware (App-M2),
  `StatementService` (App-M3), inbound facade controllers + routes (App-M4). Sequential micro-milestones,
  each gated by a targeted Pest run. **Awaiting developer approval.**

## Recent changes (per planning docs; verify before relying on)
- 2026-08-26: `dto-plan.md` approved; DTOs implemented under `app/DTOs/StatementsAPI/` (51 files).
- 2026-08-27: Transport M0–M4 completed; `api_audit_logs` migrations added.
- 2026-09-01: **M5 (mTLS + retry) + M6 (sign-off) done.** `StatementsApiClientTest` 22/22,
  `StatementsApiClientConfigTest` 6/6, full Pest suite **73/73 (438 assertions)**.
  **D1 RESOLVED** (ADR-001, OAuth 2.0 Client Credentials Grant via `OAuth2TokenManager` + static-key
  fallback); **D3 RESOLVED** (ADR-003, standard Guzzle `cert`/`ssl_key`).
- 2026-09-02: Application-layer DRAFT blueprint drafted (App-M1–M4); **awaiting approval**.
  `config/absa.php` `statements` gained `oauth_token_url`, `token_cache_key`, `token_ttl_buffer`,
  `audit_enabled`, `audit_queue`.
- 2026-09-03: **App-M1 (`OAuth2TokenManager`) implemented + tested.** Concrete manager + interface
   (`getValidToken(): string`), `OAuthTokenResponseDTO`, `TokenAcquisitionException`. 6 hermetic tests
   (`OAuth2TokenManagerTest`, 22 assertions). Full suite **79/79 (460 assertions)**. Fixed an
   `errorBody()` helper collision with `StatementsApiClientTest` (renamed to `oauthErrorBody`).

## Next steps (ordered)
1. **App-M1 — `OAuth2TokenManager` — DONE** (2026-09-03): OAuth2 Client Credentials Grant + Cache
   caching + auto-refresh; static `api_key` fallback (ADR-001). `OAuthTokenResponseDTO` +
     `TokenAcquisitionException` added; 6 hermetic tests.
2. **Get approval** for the remaining application-layer DRAFT (App-M2 → App-M4), then execute
   sequentially, each gated by a targeted Pest run.
3. **App-M2 — `ApiAuditLogger` middleware** + `AuditLogSanitizer` + queued `RecordApiAuditLog`.
4. **App-M3 — `StatementService`** (consumes `StatementsApiClientInterface` +
   `OAuth2TokenManagerInterface`); **App-M4 — inbound facade controllers + `routes/statements.php`**.
5. After Statements application layer: begin **PayShap** then **AVS** (each: spec → DTOs → transport).
6. Wire remaining config keys (`token_cache_ttl_seconds`, `redact_keys`) into `config/absa.php`.

## Open decisions / blockers
- **D1 (auth):** RESOLVED (ADR-001, 2026-09-01) — OAuth 2.0 Client Credentials Grant via
  `OAuth2TokenManager` + static `Authorization: Bearer {api_key}` fallback for local dev/testing.
- **D3 (TLS keys):** RESOLVED (ADR-003, 2026-09-01) — standard Guzzle `cert`/`ssl_key`.
- **D2 (structure):** RESOLVED — concrete transport stays under `App\DTOs\StatementsAPI\Transport\`;
  test stays at `tests/Unit/Services/StatementsAPI/`.
- **Application layer:** App-M1 (`OAuth2TokenManager`) implemented + tested (2026-09-03); App-M2 (`ApiAuditLogger` middleware) also implemented + tested (2026-09-04); App-M3 →
  App-M4 still DRAFT, awaiting developer approval.
- No CI pipeline exists; gates (G1–G10) enforced by agent + manual review until CI is added.

## Patterns & preferences to honour
- `declare(strict_types=1)` + `final readonly class` DTOs; native string-backed enums.
- Exact OpenAPI key casing in `toArray()` (e.g. `CreditDebitIndicator`, `FileCreationDateTime`).
- Namespaced enum values (`ZA.ABSA.*`) → safe case names, raw string as backing value.
- `CurrencyCode` stays a value DTO (ISO-4217), NOT an enum (documented exception).
- Hermetic unit tests: zero DB/network; use `Http::fake()` for transport.
- All CLI runs inside the `absa84_api` container (PHP 8.4), never host (PHP 8.2.31).
- **mTLS (D3):** `sslOptions()` maps `cert`/`ssl_key`/`ssl_cert_verify`/`ssl_cert_allow_self_signed`
  to Guzzle `cert`/`ssl_key`/`verify`/`verify_peer`/`verify_peer_name`; retry-on-5xx/429 via `Http::retry()`.
- **Auth (D1, ADR-001):** OAuth 2.0 Client Credentials Grant via `OAuth2TokenManager` + static
  `Authorization: Bearer {api_key}` fallback for local dev/testing.
- **Config seam:** `config('absa.statements')` (not bare `config('absa')`); env vars use
  `ABSA_STATEMENTS_*` prefix (e.g. `ABSA_STATEMENTS_BASE_URL`, `ABSA_STATEMENTS_API_KEY`,
  `ABSA_STATEMENTS_OAUTH_CLIENT_ID`).

## Learnings / insights
- No DTO/enum library is installed (no Spatie/Invictus) — DTOs are native PHP, zero new deps.
- `config('absa.statements')` is the config seam (not bare `config('absa')`).
- `retry_attempts`/`retry_delay_ms` were deferred from M1 to M2/M5 (now implemented in M5).
- `.agents/skills/` tree is inconsistent/partially broken — remediation required before Phase 1 QA role.
- **Application layer: App-M1 (`OAuth2TokenManager`) is implemented + tested (2026-09-03);
   App-M2 → App-M4 remain DRAFT, awaiting developer approval.**
- **OAuth2 config keys** (`oauth_token_url`, `token_cache_key`, `token_ttl_buffer`, `audit_enabled`,
  `audit_queue`) added to `config/absa.php`; `token_cache_ttl_seconds`/`redact_keys` still to wire.
- **Pest helper-function collision:** top-level `function` helpers in a test file are namespace-scoped
   (`Tests\Unit\Services\StatementsAPI`); a duplicate name across two files in the same namespace
   triggers `Cannot redeclare function` when the full suite loads both. `OAuth2TokenManagerTest`'s
   `errorBody()` collided with `StatementsApiClientTest`'s — renamed to `oauthErrorBody()`.
- **Laravel 13.25 removed `Cache::fake()`.** Hermetic cache tests rely on the test env's
   `CACHE_STORE=array` (phpunit.xml) for a fresh in-memory store per test instead.
- **`Request::body()` (not `Request::content()`)** asserts form-encoded request data under `Http::fake()`.
