# Progress — ABSA API Hub

> What works, what's left, current status, known issues, decision evolution. Last updated: 2026-10-06.

## Overall status
**Phase 0 (Bootstrap/Discovery) → early implementation.** The Phase 0 proposal
(`Planning/00_PROJECT_DISCOVERY_PROPOSAL.md`) is **DRAFT, awaiting developer approval**.
The **Statements API DTO + transport layers are 100% complete and signed off**
(M0–M6 done 2026-09-01), and **App-M1 (`OAuth2TokenManager`) + App-M2 (`ApiAuditLogger`) +
App-M3 (`StatementService`) + App-M4 (inbound facade controllers & routes) are all implemented
+ tested** (2026-09-03 / 2026-09-04). **On 2026-10-06 the outbound ABSA auth was reworked** from the
OAuth2 Client Credentials grant to the **Resource Owner Password grant** (`grant_type=password`,
mTLS via a single p12 cert) with real credentials from ABSA — full Statements suite
**64/64 (299 assertions)**, full Pest suite **117/117 (636 assertions)** green
(`php -l` + Pint clean), ADR-001 updated. Round 2 (same day):
the static `API_KEY` was removed and the previously-broken token wiring fixed — the OAuth2 bearer
token is now threaded through the runtime config slot (`absa.statements.api_key`) into the
transport's `Authorization: Bearer` header.

## What works (verified / reported)
- **Full Pest suite 117/117 (636 assertions) verified 2026-10-06** (`php -l` + Pint clean);
  Statements domain suite is 64/64 (299 assertions).
- **ABSA base URL = `https://api.absa.africa/cheque-statements/v1.0` (2026-10-06, round 3).** The
  production gateway serves the Statements paths under a `/v1.0` prefix; the earlier
  `/cheque-statements` (no version) base caused empty-body 404s.
- **Descriptive transport errors (2026-10-06, round 3):** `StatementsApiException` captures
  `method`/`url`/`body` (≤500-char raw preview) and builds self-describing messages for empty or
  non-JSON 4xx/5xx instead of the old bare `Statements API error 404`.
- **ABSA auth (2026-10-06):** `OAuth2TokenManager` POSTs `grant_type=password` with `client_id`,
  `scope`, `username`, `password` to `https://mtls.auth.absaaccess.africa/connect/token` over mTLS
  (`statements.cert_path` p12 + `passphrase`), caches the token for `expires_in - token_ttl_buffer`.
  **No static `api_key` fallback** — missing credentials throw `TokenAcquisitionException`.
  `config/absa.php` added `scope`/`username`/`password` and removed `client_secret`/`key_path`/
  `api_key`; default `base_url` = `https://api.absa.africa/cheque-statements`.
  `StatementsApiClientConfig` is p12-only (`sslOptions()` emits just `cert`). **Token wiring fixed:**
  `StatementService::resolveToken()` → runtime config slot `absa.statements.api_key` →
  `StatementsApiClient::withAuthorization()` (`Bearer {token}`). Real creds in `.env`;
  `.env.example` synced. `OAuth2TokenManagerTest` is 6 tests / 22 assertions.
- **Statements DTO layer** — implemented under `app/DTOs/StatementsAPI/` (~40 files):
  - `Support/` (Arrayable, FromArray, BaseDto, StatementsApiClientInterface)
  - `Enums/` (10), `Errors/` (ErrorResponseDTO, ErrorDetailDTO)
  - `Models/` (13), `Requests/` (10 incl. `Query/`), `Responses/` (6)
  - `Transport/` (StatementsApiClient, StatementsApiClientConfig, StatementsApiException)
- **Transport milestones M0–M6 done (M6 sign-off 2026-09-01):**
  - M0 contract + error type; M1 config value object (+3/3 tests); M2 client skeleton;
  - M3 8 happy-path `Http::fake` tests; M4 8 error-path tests (400/401/403/404/429/500 +
    nested detail decode + non-JSON null error); M5 mTLS + retry (`sslOptions()` mapping + retry-on-5xx/429). `StatementsApiClientTest` 22/22, `StatementsApiClientConfigTest` 6/6, full suite 73/73 (438 assertions).
- **Test files present (12):** under `tests/Unit/DTOs/StatementsAPI/` (Enums, Errors, Requests,
  Responses, Support) and `tests/Unit/Services/StatementsAPI/` (Config + Client).
- **Migrations** for `api_audit_logs` (+ environment column) added.
- **Internal auth** via Sanctum (`/v1/login`, `/v1/user`).
- Full Pest suite **73/73 (438 assertions)** at M6 sign-off (2026-09-01) — verified this session.
- **App-M1 — `OAuth2TokenManager` (2026-09-03):** `App\Services\StatementsAPI\OAuth2TokenManager`
  (concrete, `final class`) + `Contracts\OAuth2TokenManagerInterface` (`getValidToken(): string`),
  `Responses/OAuth/OAuthTokenResponseDTO`, `Transport/TokenAcquisitionException`. OAuth2 Resource
  Owner Password grant + Cache caching (`expires_in - token_ttl_buffer`, floored at 0); no static
  `api_key` fallback (ADR-001). 6 hermetic tests (`OAuth2TokenManagerTest`, 22 assertions).
- **App-M2 — `ApiAuditLogger` + `AuditLogSanitizer` + `RecordApiAuditLog` (2026-09-04):**
  `app/Http/Middleware/ApiAuditLogger.php` (middleware, dispatches a queued job with named args),
  `app/Services/StatementsAPI/Support/AuditLogSanitizer.php` (pure redaction: UPPERCASE header keys,
  nested payload redaction incl. `refresh_token`, non-JSON bodies preserved), `app/Jobs/StatementsAPI/RecordApiAuditLog.php`
  (writes **only the real `api_audit_logs` columns** — persistence is sanitized-only). 8 unit tests
  (`AuditLogSanitizerTest`) + 6 feature tests (`ApiAuditLoggerTest`, `Queue::fake()`), 69 assertions.
- **App-M3 — `StatementService` (2026-09-04):** `App\Services\StatementsAPI\StatementService`
  (final, orchestrates the 8 operations; constructor-injects `StatementsApiClientInterface` +
  `OAuth2TokenManagerInterface`; resolves the bearer token ahead of every call — consumed through the
  runtime config slot `absa.statements.api_key` per ADR-001; exposes `resolvedToken()`; propagates `StatementsApiException`
  unchanged). Hermetic double `tests/Unit/DTOs/StatementsAPI/Support/FakeStatementsApiClient.php`
  (in-memory, zero DB/network, records `called`/`received`). 3 tests (`StatementServiceTest`, 30
  assertions).
- **App-M4 — Inbound Facade Controllers & routes (2026-09-04):**
  `App\Http\Controllers\StatementsAPI\` (`Health`/`Balances`/`Statements`/`StatementTransactions`),
  Form Requests under `App\Http\Requests\StatementsAPI\` (abstract base merging route params into
  `validationData()` — required because Laravel 13 no longer auto-merges route params into
  `validated()`), routes in `routes/statements.php` (Sanctum-protected `api/v1/statements/*`, loaded
  via `withRouting(then:)` in `bootstrap/app.php`), container bindings for
  `StatementsApiClientInterface` / `OAuth2TokenManagerInterface` / `StatementService` in
  `AppServiceProvider`, and typed JSON error envelopes for `StatementsApiException` /
  `TokenAcquisitionException` in `bootstrap/app.php` (upstream 4xx/5xx mirrored, status 0 →
  502). Feature tests `tests/Feature/StatementsAPI/*` — 4 Pest files + `Support/FacadeTestSupport`
  (`StubOAuth2TokenManager` + `wire()`), 19 tests / 65 assertions: 401 unauthenticated,
  `Sanctum::actingAs` 200 envelopes, outbound `Http::fake()` request/query/header assertions,
  422 validation, and upstream-error mapping.
- **App-M4 required fixes during execution:** `phpunit.xml` gained `LOG_CHANNEL=daily` (the literal
  string `null` is cast to PHP null by Laravel's `env()` so it silently fell back to
  `.env`'s `log_stack` → `mysql_fingo_logs` writes during exception reporting); a stray
  `var_dump($e->getMessage(), $e->getTraceAsString()); exit;` debug line injected into
  `vendor/laravel/framework/.../Exceptions/Handler.php` `reportThrowable()` was removed (it was
  killing any test that reported an exception). Full suite now **115/115 (624 assertions)**.
- **All tests Pest-native + root docs refreshed (2026-09-07):** migrated the last 5 PHPUnit-style
  test files (`tests/Unit/ExampleTest.php`, `tests/Feature/ExampleTest.php`,
  `tests/Unit/DTOs/StatementsAPI/Support/BaseDtoTest.php`, `AuditLogSanitizerTest.php`,
  `tests/Feature/Http/Middleware/ApiAuditLoggerTest.php`) to `uses(TestCase::class)` + `it()` /
  top-level `it()`. Full Pest suite **116/116 (627 assertions)**; no `extends TestCase` /
  `PHPUnit\Framework\TestCase` remains in `tests/`. `PROJECT.md` updated (status, Application
  Structure table, facade route map, completed priorities); `README.md` rewritten with a Laravel
  focus + explicit ABSA-hub overview + link to `PROJECT.md`.
- **Postman collection covers the Statements facade (2026-09-08):**
  `POSTMAN_COLLECTION/` is versioned (gitignore entry removed), documented in `README.md`/`PROJECT.md`
  and shipped with a usage guide (`POSTMAN_COLLECTION/README.md`). The collection contains AUTH
  (`Login` → sets `ACCESS_TOKEN` in the collection bearer auth, `Check User`), the 7 Statements
  facade GETs under `/api/v1/statements/*` (balances ×2, statements ×3, transactions, intraday) and
  `Get System Health`. The LOCAL environment (`ABSA API LOCAL.postman_environment.json`) is
  simplified to `APP_URL` (carrying the `/api/v1` base, e.g.
  `http://absa84.payaccsys.local:8089/api/v1`), `USER_EMAIL`, `USER_PASSWORD` and `ACCESS_TOKEN` —
  the `Login` request body uses the same `{{USER_EMAIL}}`/`{{USER_PASSWORD}}` placeholders.

## What's left to build
- **M5:** ✅ done 2026-09-01 — mTLS `sslOptions()` mapping (standard Guzzle `cert`/`ssl_key`) + retry-on-5xx/429 tests; see ADR-003.
- **M6:** ✅ done 2026-09-01 — sign-off (full `pest` 73/73, 438 assertions; `php -l` clean on all 4 transport/support files; planning + memory-bank docs updated). Statements API Transport Layer 100% complete.
- **D1 (auth):** ✅ resolved 2026-09-01, **reworked 2026-10-06** — OAuth 2.0 Resource Owner Password
  grant via `OAuth2TokenManager` (no static-key fallback).
  (acquisition + Redis/Cache caching + auto-refresh), with a static `Authorization: Bearer {api_key}`
  fallback for local dev/testing; see ADR-001.
- **Statements API (Application & Domain Layer)** — App-M1 (`OAuth2TokenManager`) ✅ DONE 2026-09-03;
  App-M2 (`ApiAuditLogger`) ✅ DONE 2026-09-04; App-M3 (`StatementService`) ✅ DONE 2026-09-04;
  App-M4 (inbound facade controllers & routes) ✅ DONE 2026-09-04. Application layer complete.
  Micro-milestones M1–M4, sequential; each gated by a targeted Pest run before proceeding.
    - **App-M1 — `OAuth2TokenManager` — ✅ DONE 2026-09-03** (ADR-001): `App\Services\StatementsAPI\OAuth2TokenManager`
      (concrete, `final class`) + `Contracts\OAuth2TokenManagerInterface` (`getValidToken(): string`). OAuth2
      **Resource Owner Password** grant + Cache caching (`expires_in - token_ttl_buffer`, floored at 0);
      non-2xx / undecodable / no-credential → `TokenAcquisitionException` (status + decoded `ErrorResponseDTO`).
      New DTO `Responses/OAuth/OAuthTokenResponseDTO` + `Transport/TokenAcquisitionException`. 6 hermetic tests
      (`OAuth2TokenManagerTest`, 22 assertions; `Http::fake()` + `CACHE_STORE=array` — `Cache::fake()` removed in L13.25).
    - **App-M2 — `ApiAuditLogger` middleware — ✅ DONE 2026-09-04** (`App\Http\Middleware\ApiAuditLogger`):
      sanitize payload/headers (redact Bearer/secrets via pure `App\Services\StatementsAPI\Support\AuditLogSanitizer`);
      async capture to `api_audit_logs` via queued `App\Jobs\StatementsAPI\RecordApiAuditLog`. Tests:
      `AuditLogSanitizerTest` (Unit, hermetic, 8 tests) + `tests/Feature/Http/Middleware/ApiAuditLoggerTest.php`
      (`Queue::fake()`, 6 tests). Bug-fix round (2026-09-04): sanitizer UPPERCASE header keys; null-safe payload;
      job writes only real `api_audit_logs` columns (sanitized-only persistence, `environment` included);
      named-arg dispatch; tests use `Request::create()`. 14 tests / 69 assertions; full suite **93/93 (529 assertions)**.
    - **App-M3 — `StatementService` — ✅ DONE 2026-09-04** (`App\Services\StatementsAPI\StatementService`):
      constructor-injects `StatementsApiClientInterface` + `OAuth2TokenManagerInterface`; resolves token
      via `getValidToken()` ahead of every call (injected into the runtime config slot
      `absa.statements.api_key`,
      ADR-001); maps response DTOs; propagates `StatementsApiException` unchanged. Hermetic double
      `tests/Unit/DTOs/StatementsAPI/Support/FakeStatementsApiClient.php` (zero DB/network; records
      `called`/`received`). Tests `tests/Unit/Services/StatementsAPI/StatementServiceTest.php` —
      3 tests / 30 assertions (token resolution + runtime-slot injection, all 8 operations' request/response
      mapping, exception propagation).
    - **App-M4 — Inbound Facade Controllers & routes — ✅ DONE 2026-09-04** (19 tests / 65 assertions):
      `App\Http\Controllers\StatementsAPI\` (`Health`/`Balances`/`Statements`/`StatementTransactions`)
      injecting `StatementService`; Form Requests under `App\Http\Requests\StatementsAPI\` (abstract
      base + 6 concrete; route params merged into `validationData()` — Laravel 13 no longer does this
      automatically); routes in `routes/statements.php` (Sanctum-protected `api/v1/statements/*`,
      loaded via `withRouting(then:)`); `AppServiceProvider` bindings (client, token manager,
      `StatementService` singleton); `bootstrap/app.php` JSON error envelopes for
      `StatementsApiException` / `TokenAcquisitionException`. Full suite **115/115 (624 assertions)**.
    - **Config keys** — `config/absa.php` `statements` now defines `oauth_token_url`, `token_cache_key`
      (`absa.statements.oauth_token`), `token_ttl_buffer` (60), `audit_enabled` (true), `audit_queue` (default).
      Still pending: `token_cache_ttl_seconds` (300 fallback) and `redact_keys`
      (`authorization,api_key,client_secret,password,token,bearer`). Mirrored in `.env.example` under `ABSA_STATEMENTS_*`.
    - **Verify at execution (not yet confirmed):** `StatementsApiException` location; `StatementsApiClient`
      constructor shape (token-injection seam); whether `api_audit_logs` migration already exists; Sanctum guard.
    - **Docs/memory sync on approval:** write blueprint to
      `Planning/03_APIS/STATEMENTS/application-layer-plan.md`; update this `progress.md`.
- **PayShap Request API** — not started (spec → DTOs → transport).
- **AVS** — not started.
- **`docs/` directory** — does not exist yet; create it and promote approved planning into it
  (a documented next step; `PROJECT.md`/`README.md` already reference it).
- `Planning/99_Decisions/` ADR-001 (external auth architecture, D1) and ADR-003 (mTLS option keys, D3) created 2026-09-01.
- `.agents/skills/` remediation (inconsistent/partially broken) before Phase 1 QA role.
- CI pipeline (none exists) — future, not Phase 1.

## Known issues / risks
- **R-1:** host PHP 8.2.31 vs required 8.4 — must always run in the container.
- **Q-1:** `laravel/boost` recommended by README but not installed.
- **D1 RESOLVED** (ADR-001, 2026-09-01); **D3 RESOLVED** (ADR-003, 2026-09-01).
- No CI; gates (G1–G10) are agent + manual review only.
- Planning docs may drift from code — **re-verify test counts and milestone status** before trusting them.

## Decision evolution (chronological)
- 2026-08-19: Phase 0 discovery proposal drafted (DRAFT).
- 2026-08-26: `dto-plan.md` approved; DTOs implemented.
- 2026-08-27: Transport M0–M4 completed; `api_audit_logs` migrations added.
- 2026-09-01: Memory bank initialised (this file).
- **D2 RESOLVED:** transport stays in `App\DTOs\StatementsAPI\Transport\`; test in
  `tests/Unit/Services/StatementsAPI/`.
- **D3 RESOLVED** (ADR-003, standard Guzzle `cert`/`ssl_key`); **D1 RESOLVED** (ADR-001, OAuth 2.0
   Client Credentials Grant via `OAuth2TokenManager` + static-key fallback, 2026-09-01).
- **2026-09-03:** App-M1 (`OAuth2TokenManager`) implemented + tested; full suite 79/79 (460 assertions).
   Fixed `errorBody()` helper collision (renamed `oauthErrorBody` in `OAuth2TokenManagerTest`).
- **2026-09-04:** App-M2 (`ApiAuditLogger` + `AuditLogSanitizer` + `RecordApiAuditLog`) implemented +
   tested; then a bug-fix round (sanitizer UPPERCASE header keys + null-safe payload + `refresh_token`
   redaction; job persists only real `api_audit_logs` columns with sanitized data; named-arg dispatch;
   `Request::create()` in feature tests). Full suite **93/93 (529 assertions)**; `php -l` + Pint clean.
- **2026-09-04:** App-M3 (`StatementService` + `FakeStatementsApiClient` + `StatementServiceTest`)
   implemented + tested. Full suite **96/96 (559 assertions)**; `php -l` + Pint clean.
- **2026-09-04:** App-M4 (facade controllers + Form Requests + `routes/statements.php` + routes/
   container wiring + error envelopes) implemented + tested. Fixed in passing: Laravel 13
   `FormRequest::validationData()` does not auto-merge route params (override in
   `AbstractStatementsRequest`); `phpunit.xml` `LOG_CHANNEL` must be a real channel (`daily`, not the
   literal string `null` which Laravel casts to PHP null → falls back to `log_stack` → MySQL writes);
   `Http::fake()` URL patterns don't match query-bearing URLs (`*` suffix needed); removed a stray
   `var_dump(); exit;` debug line from `vendor/.../Exceptions/Handler.php::reportThrowable()` that
   killed any test reporting an exception. Full suite **115/115 (624 assertions)**; `php -l` + Pint clean.
- **2026-09-07:** Migrated the last PHPUnit-style tests to Pest (5 files; no PHPUnit references left
   in `tests/`); full suite **116/116 (627 assertions)**; `php -l` + Pint clean. Updated `PROJECT.md`
   and `README.md` (project status, application structure/route map, Laravel-focused README linking
   to `PROJECT.md`).
- **2026-09-08:** Populated the Postman `StatementsAPI` folder with the 7 facade endpoints and
   removed `/POSTMAN_COLLECTION` from `.gitignore` — the collection is now versioned and documented
   in `README.md`/`PROJECT.md`.
- **2026-09-08 (same day):** developer finalised the Postman files — `Login` body now uses the
   simplified `{{USER_EMAIL}}`/`{{USER_PASSWORD}}` (dropped `_PHOENIX`/`_SUPER`/`BEARER_*` env vars),
   `APP_URL` includes the `/api/v1` prefix so all request paths resolve, and
   `POSTMAN_COLLECTION/README.md` was expanded into a full usage guide (layout, auth, variables,
   troubleshooting).
- **2026-10-06 (round 7, same day): Postman collection exposes pagination.** All 6 listing
   requests now carry `pg`/`pgSize` (collection vars `pg=1`/`pgSize=100`); README documents the
   pagination section.
- **2026-10-06 (round 6, same day): balances pagination wired.** Live balances data proved ABSA
   paginates `/balances` + `/accounts/{id}/balances` via `pg`/`pgSize` (returned in `Links`/`Meta`).
   Request DTOs + inbound Form Requests now forward `PaginationQuery` (new `GetBalancesRequest` for
   the list endpoint; `GetAccountBalancesRequest` gained `pg`/`pgSize` rules). 5 tests added
   (2 DTO hydration + 3 feature incl. 422 validation). Full suite **122/122 (649 assertions)**;
   OpenAPI spec regenerated and re-enriched.
- **2026-10-06 (round 5, docs): inbound OpenAPI V3 documentation.** Added `dedoc/scramble`
   `^0.13.47`; `config/scramble.php` (api_path `api`, bearer security strategy, docs UI gated to
   local env); exported `docs/apis/statements/openapi.json` (10 operations). Response schemas
   enriched via `app/Support/StatementsOpenApiSchemas.php` + `docs/apis/statements/enrich_openapi.php`
   (verified envelope+item shapes; deep nested ABSA models documented as honest placeholders).
   README/PROJECT.md link the spec. Full suite 117/117 (636); Pint clean.
- **2026-10-06 (round 4, docs): `README.md` / `PROJECT.md` refreshed.** README now documents the
   outbound OAuth2 password grant; PROJECT.md test count updated to ~117/636 and the Statements
   status gained the outbound-auth + descriptive-error notes.
- **2026-10-06 (round 3): ABSA base URL corrected + descriptive transport errors.** Base URL is
   `https://api.absa.africa/cheque-statements/v1.0` (production requires the `/v1.0` version
   prefix — this was the cause of the empty-body 404s; `.env`/`.env.example`/`config/absa.php`/
   `StatementsApiClientConfig` default all updated). `StatementsApiException` now records
   `method`/`url`/`body` (≤500-char preview) and produces messages such as
   `GET https://…/health failed with HTTP 404 and an empty response body.`, replacing the useless
   `Statements API error 404`. Two transport tests added (raw-body / empty-body); the feature
   connection-failure test now asserts the descriptive message. Full suite **117/117 (636
   assertions)**; `php -l` + Pint clean.
- **2026-10-06: ABSA auth reworked to the Resource Owner Password grant.** ABSA issued real
   credentials. `OAuth2TokenManager` now POSTs `grant_type=password` (`client_id`, `scope`,
   `username`, `password`, form-encoded) to `https://mtls.auth.absaaccess.africa/connect/token`
   over mTLS and caches the token (`expires_in - token_ttl_buffer`); `config/absa.php` added
   `scope`/`username`/`password` and dropped `client_secret`/`key_path`; default `base_url` =
   `https://api.absa.africa/cheque-statements`; `StatementsApiClientConfig.sslOptions()` is
   p12-only (single `cert`, no `ssl_key`); `.env`/`.env.example` synced (removed `GRANT_TYPE`,
   `TOKEN_CACHE_STORE`, `CLIENT_SECRET`, `SSL_KEY_PATH`); ADR-001 updated; mTLS test added
   (OAuth2TokenManagerTest now 7 tests / 25 assertions). Executed `Planning/UPDATED_ABSA_AUTH.md`
   tasks 1–6. *(Counts superseded by the round-2 entry below: 6 tests / 22 assertions.)*
- **2026-10-06 (round 2): `API_KEY` removed + token wiring fixed.** Dropped the static `api_key`
   credential/fallback (env, config, `OAuth2TokenManager`, `StatementsApiClientConfig`). Fixed a
   real wiring bug: the OAuth2 bearer token never reached the transport — the client sent the
   static placeholder (`your_api_key_here`) as `Authorization: Bearer` (ABSA 404). Now
   `StatementService::resolveToken()` → `config('absa.statements.api_key')` → read dynamically by
   `StatementsApiClient::withAuthorization()`. Token-manager now fails fast when password-grant
   credentials are missing (no fallback). `OAuth2TokenManagerTest` is 6 tests / 22 assertions;
   full Statements suite **64/64 (299 assertions)**; **full Pest suite 116/116 (623 assertions)**;
   `php -l` + Pint clean.

## Guardrails in force (G1–G10, from the proposal)
G1 no source-doc mutation · G2 no DB write without approval · G3 `.env`/secrets protection ·
G4 requirement-completeness gate · G5 test-before-completion · G6 API-contract verification ·
G7 in-container execution · G8 logging-isolation (0 rows to `mysql_fingo_logs` in tests) ·
G9 code-review gate · G10 migration-approval gate.
