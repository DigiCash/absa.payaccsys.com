# Active Context — ABSA API Hub

> Current focus, recent changes, next steps, open decisions. Update after significant changes.
> Last updated: 2026-10-06.

## Current focus
**ABSA Auth updated to OAuth2 Resource Owner Password grant — COMPLETE (2026-10-06).** ABSA issued
real auth credentials, so `OAuth2TokenManager` now uses `grant_type=password`
(`client_id` + `scope` + `username` + `password`) against
`https://mtls.auth.absaaccess.africa/connect/token`, with mTLS via a single `.p12` client cert +
passphrase. The Statements base URL is now `https://api.absa.africa/cheque-statements`.
`config/absa.php` gained `scope`/`username`/`password` and dropped `client_secret`/`key_path`/`api_key`;
`StatementsApiClientConfig.sslOptions()` no longer emits `ssl_key` (p12 bundles its own key).
Full Statements suite **64/64 (299 assertions)** green; full Pest suite **117/117
(636 assertions)**; `php -l` + Pint clean; ADR-001 updated. **Round 3 (same day):** ABSA base URL
corrected to `https://api.absa.africa/cheque-statements/v1.0` (the production gateway exposes the
paths under a `/v1.0` prefix — this was the cause of the empty-body 404s), and
`StatementsApiException` is now self-describing: it captures `method` / `url` / raw `body`
(preview, ≤500 chars) and builds messages like
`GET https://…/cheque-statements/v1.0/health failed with HTTP 404 and an empty response body.`.
Real credentials are in `.env` (`ABSA_STATEMENTS_USERNAME`/`_PASSWORD`/`_SCOPE=bifrost-gateway`,
p12 at `storage/app/private/certs/private/absa/cert.p12`). **Round 2 (same day):** `API_KEY`
removed entirely + the previously-broken token wiring fixed — the OAuth2 bearer token is now
threaded `StatementService::resolveToken()` → runtime config slot `absa.statements.api_key` →
`StatementsApiClient::withAuthorization()` (previously the placeholder `your_api_key_here` reached
the wire → 404s). Next outward steps remain PayShap / AVS.
- **Transport layer M0–M6 — DONE** (2026-09-01): `StatementsApiClient` (concrete, `Http::` facade),
  mTLS `sslOptions()` mapping + retry-on-5xx/429 (M5), sign-off (M6).
- **App-M1 — `OAuth2TokenManager` — DONE** (2026-09-03, **password grant rework 2026-10-06**): concrete
  `App\Services\StatementsAPI\OAuth2TokenManager` + `Contracts\OAuth2TokenManagerInterface`
  (`getValidToken(): string`), `Responses/OAuth/OAuthTokenResponseDTO`,
  `Transport/TokenAcquisitionException`. OAuth2 Resource Owner Password grant (`grant_type=password`,
  mTLS via `Http::withOptions(['cert' => [path, passphrase]])`) + Cache caching
  (`expires_in - token_ttl_buffer`, floored at 0); no static-key fallback (ADR-001). 6 hermetic
  tests (`OAuth2TokenManagerTest`, 22 assertions).
- **App-M2 — `ApiAuditLogger` middleware — DONE** (2026-09-04): `AuditLogSanitizer` + queued
  `RecordApiAuditLog`. 14 tests / 69 assertions.
- **App-M3 — `StatementService` — DONE** (2026-09-04): final, orchestrates the 8 operations;
  constructor-injects `StatementsApiClientInterface` + `OAuth2TokenManagerInterface`; resolves the
  bearer token ahead of every call (private `resolveToken()` → `getValidToken()`, exposed via
  `resolvedToken()` for the runtime config slot `absa.statements.api_key`, ADR-001); propagates
  `StatementsApiException` unchanged. Hermetic double `FakeStatementsApiClient` + 3 tests
  (`StatementServiceTest`, 30 assertions).
- **App-M4 — Inbound facade controllers + routes — DONE** (2026-09-04): `App\Http\Controllers\StatementsAPI\`
  (`Health`/`Balances`/`Statements`/`StatementTransactions`), Form Requests under
  `App\Http\Requests\StatementsAPI\`, `routes/statements.php` (Sanctum-protected `api/v1/statements/*`),
  container bindings in `AppServiceProvider`, typed error envelopes in `bootstrap/app.php`. 19 feature
  tests / 65 assertions (`tests/Feature/StatementsAPI/*`).

## Recent changes (per planning docs; verify before relying on)
- 2026-10-06 (**round 4, same day): root docs refreshed.** `README.md` now mentions the outbound
  OAuth2 Resource Owner Password grant (`OAuth2TokenManager`, cached + auto-refreshed, mTLS);
  `PROJECT.md` test count updated to ~117 / ~636, the Statements implementation-status block gained
  the outbound auth bullet (`grant_type=password` over p12 mTLS, real ABSA credentials 2026-10-06),
  and the application-structure table notes the grant mechanism.
- 2026-10-06 (**round 3, same day): ABSA base URL corrected + descriptive transport errors.**
  The base URL now defaults to `https://api.absa.africa/cheque-statements/v1.0` (the production
  gateway requires the `/v1.0` prefix — this was the cause of the empty-body 404s). `config/absa.php`,
  `.env`, `.env.example` and `StatementsApiClientConfig::fromConfig()` all updated.
  `StatementsApiException` gained `method`, `url` and `body` (≤500-char raw preview) and builds a
  self-describing message (e.g. `GET https://…/health failed with HTTP 404 and an empty response
  body.`), so gateway 4xx/5xx that previously surfaced as a bare `Statements API error 404` now
  name the exact failing call. `StatementsApiClient::send()` computes the full request URL and
  passes it (plus method + truncated body) into the exception for both non-2xx and
  connection-failure branches. Tests: raw-body + empty-body transport tests added; feature
  connection-failure expectation updated. Full suite **117/117 (636 assertions)**.
- 2026-10-06 (**round 2, same day): `API_KEY` removed + token wiring fixed.** The static
  `api_key` credential/fallback is gone (no `ABSA_STATEMENTS_API_KEY` env; `config/absa.php` dropped
  the key; `StatementsApiClientConfig` no longer carries `apiKey`). **Bug fix:** the OAuth2 bearer
  token resolved by `OAuth2TokenManager` previously never reached the transport — the client
  emitted the static placeholder (`your_api_key_here`) as `Authorization: Bearer`, causing ABSA
  404s. Now `StatementService::resolveToken()` writes the token to the runtime config slot
  `absa.statements.api_key` and `StatementsApiClient::withAuthorization()` reads it at call time.
  Token-manager credential check fails fast (`TokenAcquisitionException`) with no fallback.
  `OAuth2TokenManagerTest` is now 6 tests / 22 assertions (fallback test removed). Full Statements
  suite **64/64 (299 assertions)**, `php -l` + Pint clean.
- 2026-10-06: **ABSA auth updated to Resource Owner Password grant (`grant_type=password`).**
  `Planning/UPDATED_ABSA_AUTH.md` executed: `config/absa.php` gained `scope`/`username`/`password`
  (defaults: `oauth_token_url` = `https://mtls.auth.absaaccess.africa/connect/token`, `base_url` =
  `https://api.absa.africa/cheque-statements`) and dropped `client_secret`/`key_path`;
  `.env`.example/.env synced (removed `GRANT_TYPE`, `TOKEN_CACHE_STORE`, `CLIENT_SECRET`,
  `SSL_KEY_PATH`; real creds live in local `.env` only); `OAuth2TokenManager` rewritten (password
  grant + mTLS `Http::withOptions(['cert' => [p12, passphrase]])`); `StatementsApiClientConfig`
  is p12-only (no `ssl_key`); ADR-001 updated; `FacadeTestSupport` dropped `key_path` wiring.
  New mTLS test added (`OAuth2TokenManagerTest` now 7 tests / 25 assertions). Full Statements
  suite **65/65 (304 assertions)**, `php -l` + Pint clean. *(Counts superseded by round 2 above —
  current: 6 tests / 22 assertions; Statements 64/64/299; full suite 116/116/623.)*
- 2026-08-26: `dto-plan.md` approved; DTOs implemented under `app/DTOs/StatementsAPI/` (51 files).
- 2026-08-27: Transport M0–M4 completed; `api_audit_logs` migrations added.
- 2026-09-01: **M5 (mTLS + retry) + M6 (sign-off) done.** `StatementsApiClientTest` 22/22,
  `StatementsApiClientConfigTest` 6/6, full Pest suite **73/73 (438 assertions)**.
  **D1 RESOLVED** (ADR-001, OAuth 2.0 Client Credentials Grant via `OAuth2TokenManager` + static-key
  fallback); **D3 RESOLVED** (ADR-003, standard Guzzle `cert`/`ssl_key`).
- 2026-09-02: Application-layer DRAFT blueprint drafted (App-M1–M4); **awaiting approval**.
  `config/absa.php` `statements` gained `oauth_token_url`, `token_cache_key`, `token_ttl_buffer`,
  `audit_enabled`, `audit_queue`.
- 2026-09-04: **App-M2 (`ApiAuditLogger` middleware + `AuditLogSanitizer` + queued `RecordApiAuditLog`)
  implemented + tested.** Feature test (`ApiAuditLoggerTest`, 6 tests) + unit test
  (`AuditLogSanitizerTest`, 8 tests), 69 assertions. Bug-fix round: sanitizer UPPERCASE header keys,
  null-safe payload, job persists only real `api_audit_logs` columns (sanitized + `environment`),
  named-arg dispatch, `Request::create()` in tests. Full suite **93/93 (529 assertions)**.
- 2026-09-04: **App-M2 (`ApiAuditLogger` middleware + `AuditLogSanitizer` + queued `RecordApiAuditLog`)
   implemented + tested.** Feature test (`ApiAuditLoggerTest`, 6 tests) + unit test
   (`AuditLogSanitizerTest`, 8 tests), 69 assertions. Bug-fix round: UPPERCASE header-key redaction,
   null-safe payloads, job persists only real `api_audit_logs` columns with sanitized data (incl.
   `environment`), named-arg dispatch, `Request::create()` in tests. Full suite **93/93 (529 assertions)**.
- 2026-09-04: **App-M3 (`StatementService`) implemented + tested.** `App\Services\StatementsAPI\StatementService`
   (final) constructor-injects `StatementsApiClientInterface` + `OAuth2TokenManagerInterface`, resolves the
   bearer token ahead of every operation (consumed via the runtime config slot
  `absa.statements.api_key`, ADR-001),
   delegates all 8 operations and propagates `StatementsApiException` unchanged; exposes `resolvedToken()`.
   Hermetic double `tests/Unit/DTOs/StatementsAPI/Support/FakeStatementsApiClient.php` (in-memory, zero
   DB/network, records `called`/`received`). 3 tests / 30 assertions (`StatementServiceTest`). Full suite
   **96/96 (559 assertions)**.

- 2026-09-07: **Migrated the last 5 PHPUnit-style test files to Pest** (`tests/Unit/ExampleTest.php`,
  `tests/Feature/ExampleTest.php`, `Support/BaseDtoTest.php`, `AuditLogSanitizerTest.php`,
  `ApiAuditLoggerTest.php`) — removed all `extends TestCase` / `PHPUnit\Framework\TestCase` /
  `public function test_*`; app-dependent tests now use `uses(TestCase::class)` + `it()` (pattern from
  `HealthEndpointTest` / `OAuth2TokenManagerTest`); pure unit tests are top-level `it()`/`expect()`.
  Added `declare(strict_types=1)`. `php -l` + Pint clean. Full Pest suite **116/116 (627 assertions)**.
  No PHPUnit references remain in `tests/`.
- 2026-09-07: **Updated root docs.** `PROJECT.md` updated: status → *Early implementation
  (Statements API)*, added an *Application Structure* layer table + full inbound facade route map,
  mark completed planning priorities as done, updated *Where To Start*. `README.md` rewritten to keep a
  Laravel focus while making the ABSA hub project purpose explicit and linking to `PROJECT.md`.
  `docs/` dir does NOT yet exist — references to it are target-of-record only.
- 2026-09-08: **Populated and finalised the Postman collection.** Added the 7 Statements facade
  endpoints (`GET /api/v1/statements/*` — balances ×2, statements ×3, transactions, intraday) into
  the previously-empty `StatementsAPI` folder of
  `POSTMAN_COLLECTION/ABSA API.postman_collection.json`; `/POSTMAN_COLLECTION` removed from
  `.gitignore` → folder versioned and tracked, documented in `README.md` + `PROJECT.md`. The
  developer then aligned collection + environment + guide: the `Login` body now uses the simplified
  `{{USER_EMAIL}}`/`{{USER_PASSWORD}}` (the `_PHOENIX`/`_SUPER`/`BEARER_*` vars are gone) and
  `APP_URL` carries the `/api/v1` prefix — so every request path (including the older `/login`,
  `/user`, `/statements/health`) now resolves against the real routes.

## Next steps (ordered)
1. **App-M1 — `OAuth2TokenManager` — DONE** (2026-09-03): OAuth2 Resource Owner Password grant +
   Cache caching + auto-refresh; no static-key fallback (ADR-001). `OAuthTokenResponseDTO` +
     `TokenAcquisitionException` added; 6 hermetic tests.
2. **App-M2 — `ApiAuditLogger` middleware — DONE** (2026-09-04): `AuditLogSanitizer` + queued
   `RecordApiAuditLog`. FIX round: UPPERCASE key redaction, null-safe payload, job persists only
   real `api_audit_logs` columns (sanitized + `environment`), named-arg dispatch. 14 tests (69 assertions).
3. **App-M3 — `StatementService` — DONE** (2026-09-04): constructor-injects
   `StatementsApiClientInterface` + `OAuth2TokenManagerInterface`; resolves token via `getValidToken()`
   ahead of every call (injected into the runtime config slot `absa.statements.api_key`, ADR-001); maps response
   DTOs; propagates `StatementsApiException` unchanged. Hermetic double `FakeStatementsApiClient` +
   3 tests (`StatementServiceTest`, 30 assertions).
4. **App-M4 — Inbound facade controllers + `routes/statements.php`** — DONE (2026-09-04,
   Sanctum-protected `/v1/statements/*`).
5. **Test migration + docs — DONE** (2026-09-07): all tests Pest-native (116/116); `PROJECT.md` +
   `README.md` refreshed.
6. After Statements application layer: begin **PayShap** then **AVS** (each: spec → DTOs → transport).
7. Wire remaining config keys (`token_cache_ttl_seconds`, `redact_keys`) into `config/absa.php`.
8. Promote approved planning into the not-yet-created `docs/` directory; remediate `.agents/skills/`.

## Open decisions / blockers
- **D1 (auth):** RESOLVED (ADR-001, 2026-09-01; **grant updated 2026-10-06**) — OAuth 2.0 Resource
  Owner **Password** grant via `OAuth2TokenManager` (`grant_type=password` with `client_id + scope +
  username + password` over mTLS); **no static-key fallback** — missing credentials fail fast.
- **D3 (TLS keys):** RESOLVED (ADR-003, 2026-09-01; **p12-only since 2026-10-06**) — standard Guzzle
  `cert` (p12 + optional passphrase); no separate `ssl_key`.
- **D2 (structure):** RESOLVED — concrete transport stays under `App\DTOs\StatementsAPI\Transport\`;
  test stays at `tests/Unit/Services/StatementsAPI/`.
- **Application layer:** App-M1 (`OAuth2TokenManager`), App-M2 (`ApiAuditLogger`), App-M3
  (`StatementService`) and App-M4 (inbound facade controllers + routes) ALL implemented + tested
  (2026-09-03 / 2026-09-04). No pending application-layer blocker for Statements.
- **Docs:** `docs/` directory does not yet exist; `PROJECT.md`/`README.md` reference it as the
  target for approved knowledge (create + promote planning into it next).
- No CI pipeline exists; gates (G1–G10) enforced by agent + manual review until CI is added.

## Patterns & preferences to honour
- `declare(strict_types=1)` + `final readonly class` DTOs; native string-backed enums.
- Exact OpenAPI key casing in `toArray()` (e.g. `CreditDebitIndicator`, `FileCreationDateTime`).
- Namespaced enum values (`ZA.ABSA.*`) → safe case names, raw string as backing value.
- `CurrencyCode` stays a value DTO (ISO-4217), NOT an enum (documented exception).
- Hermetic unit tests: zero DB/network; use `Http::fake()` for transport.
- All CLI runs inside the `absa84_api` container (PHP 8.4), never host (PHP 8.2.31).
- **mTLS (D3):** `sslOptions()` maps the single **p12 cert** to Guzzle `cert` (bare path, or
  `[path, passphrase]` when a passphrase is set); **no `ssl_key`** — the p12 bundle carries its own
  key. Applied to **both** the token request and the Statements API calls; retry-on-5xx/429 via
  `Http::retry()`.
- **Auth (D1, ADR-001):** OAuth 2.0 **Resource Owner Password grant** (`grant_type=password`) via
  `OAuth2TokenManager` — there is **no static-key fallback**; missing credentials throw
  `TokenAcquisitionException`. `StatementService::resolveToken()` writes the resolved token into the
  runtime config slot `absa.statements.api_key`, and `StatementsApiClient::withAuthorization()`
  reads it back at call time to emit `Authorization: Bearer {token}`. POST body:
  `grant_type=password, client_id, scope, username, password` (form-encoded over mTLS).
- **Config seam:** `config('absa.statements')` (not bare `config('absa')`); env vars use
  `ABSA_STATEMENTS_*` prefix (e.g. `ABSA_STATEMENTS_BASE_URL`, `ABSA_STATEMENTS_API_KEY`,
  `ABSA_STATEMENTS_USERNAME`, `ABSA_STATEMENTS_SCOPE`, `ABSA_STATEMENTS_PASSWORD`).

## Learnings / insights
- No DTO/enum library is installed (no Spatie/Invictus) — DTOs are native PHP, zero new deps.
- **All tests are Pest-native (2026-09-07).** The repo previously mixed PHPUnit classes with Pest
  `it()`. Migrating: drop `extends TestCase` / `PHPUnit\Framework\TestCase`, convert
  `public function test_*` to top-level `it('...', fn () => ...)`; app-bootstrapping tests need
  `uses(TestCase::class)`, while pure-unit tests (no Laravel app) use top-level `it()` only. A plain
  static helper class may remain in a test file (e.g. `BaseDtoTest`) — it is NOT a test case. After
  migrating, Pint (`--dirty`) cleans leftovers (e.g. unused `use` imports, indentation).
- **Postman collection** (`POSTMAN_COLLECTION/`, versioned from 2026-09-08) mirrors the inbound
  facade: `Login` auto-captures `ACCESS_TOKEN` into the collection's Bearer auth, the `StatementsAPI`
  folder holds one `GET` per `/api/v1/statements/*` operation, and `POSTMAN_COLLECTION/README.md` is
  the usage guide. The key to consistency: **`APP_URL` includes the `/api/v1` prefix** (e.g.
  `http://absa84.payaccsys.local:8089/api/v1`), so bare request paths like `{{APP_URL}}/login` and
  `{{APP_URL}}/statements/health` resolve correctly. The `Login` body and the LOCAL environment use
  the same simplified variables (`USER_EMAIL`, `USER_PASSWORD`, `ACCESS_TOKEN`) — no `BEARER_*` /
  `_SUPER` / `_PHOENIX` leftovers; do not reintroduce them.
- `config('absa.statements')` is the config seam (not bare `config('absa')`).
- `retry_attempts`/`retry_delay_ms` were deferred from M1 to M2/M5 (now implemented in M5).
- `.agents/skills/` tree is inconsistent/partially broken — remediation required before Phase 1 QA role.
- **Application layer: App-M1 (`OAuth2TokenManager`), App-M2 (`ApiAuditLogger` middleware),
  App-M3 (`StatementService`) and App-M4 (inbound facade controllers + routes) are all
  implemented + tested (2026-09-03 / 2026-09-04) — the application layer is COMPLETE.**
- **OAuth2 config keys** (`oauth_token_url`, `token_cache_key`, `token_ttl_buffer`, `audit_enabled`,
  `audit_queue`) added to `config/absa.php`; `token_cache_ttl_seconds`/`redact_keys` still to wire.
- **Audit redaction keys are case-insensitive and recursive:** `AuditLogSanitizer` keeps header keys
  UPPERCASE in output so downstream comparisons are deterministic; payload redaction covers
  `refresh_token` and stops redacting bare `key` (too broad — breaks legit data).
- **Job `handle()` must match the real `api_audit_logs` columns.** The `api_audit_logs` migration has
  NO `sanitized_request` / `sanitized_response` / `ip_address` / `user_agent` columns — map only
  sanitized data into the real columns (`request_headers`, `request_payload`, `response_payload`,
  `environment`, etc.) or the insert fails on a real DB.
- **Middleware → job dispatch must match the constructor arity/order:** named arguments prevent
  silent positional misalignment (e.g. method landing in `direction`).
- **Feature tests on middleware should build requests with `Request::create('/path')`** — a bare
  `new Request()` yields `fullUrl() === 'http://:'`, which breaks endpoint assertions.
- **Pest helper-function collision:** top-level `function` helpers in a test file are namespace-scoped
   (`Tests\Unit\Services\StatementsAPI`); a duplicate name across two files in the same namespace
   triggers `Cannot redeclare function` when the full suite loads both. `OAuth2TokenManagerTest`'s
   `errorBody()` collided with `StatementsApiClientTest`'s — renamed to `oauthErrorBody()`.
- **Laravel 13.25 removed `Cache::fake()`.** Hermetic cache tests rely on the test env's
   `CACHE_STORE=array` (phpunit.xml) for a fresh in-memory store per test instead.
- **`Request::body()` (not `Request::content()`)** asserts form-encoded request data under `Http::fake()`.
