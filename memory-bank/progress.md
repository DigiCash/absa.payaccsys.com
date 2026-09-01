# Progress — ABSA API Hub

> What works, what's left, current status, known issues, decision evolution. Last updated: 2026-09-01.

## Overall status
**Phase 0 (Bootstrap/Discovery) → early implementation.** The Phase 0 proposal
(`Planning/00_PROJECT_DISCOVERY_PROPOSAL.md`) is **DRAFT, awaiting developer approval**.
Despite that, the **Statements API DTO + transport layers are already being implemented**
(per the approved `dto-plan.md` and `client-transport-plan.md`).

## What works (verified / reported)
- **Statements DTO layer** — implemented under `app/DTOs/StatementsAPI/` (~40 files):
  - `Support/` (Arrayable, FromArray, BaseDto, StatementsApiClientInterface)
  - `Enums/` (10), `Errors/` (ErrorResponseDTO, ErrorDetailDTO)
  - `Models/` (13), `Requests/` (10 incl. `Query/`), `Responses/` (6)
  - `Transport/` (StatementsApiClient, StatementsApiClientConfig, StatementsApiException)
- **Transport milestones M0–M5 done (M5 2026-09-01):**
  - M0 contract + error type; M1 config value object (+3/3 tests); M2 client skeleton;
  - M3 8 happy-path `Http::fake` tests; M4 8 error-path tests (400/401/403/404/429/500 +
    nested detail decode + non-JSON null error); M5 mTLS + retry (`sslOptions()` mapping + retry-on-5xx/429). `StatementsApiClientTest` 22/22, `StatementsApiClientConfigTest` 6/6, full suite 73/73 (438 assertions).
- **Test files present (12):** under `tests/Unit/DTOs/StatementsAPI/` (Enums, Errors, Requests,
  Responses, Support) and `tests/Unit/Services/StatementsAPI/` (Config + Client).
- **Migrations** for `api_audit_logs` (+ environment column) added.
- **Internal auth** via Sanctum (`/v1/login`, `/v1/user`).
- Full Pest suite **73/73 (438 assertions)** at M5 (2026-09-01) — verified this session.

## What's left to build
- **M5:** ✅ done 2026-09-01 — mTLS `sslOptions()` mapping (standard Guzzle `cert`/`ssl_key`) + retry-on-5xx/429 tests; see ADR-003.
- **M6:** sign-off (full `pest` green, `php -l` all new files, planning docs updated).
- **D1 (auth):** ✅ resolved 2026-09-01 — OAuth 2.0 Client Credentials Grant via `OAuth2TokenManager`
  (acquisition + Redis/Cache caching + auto-refresh), with a static `Authorization: Bearer {api_key}`
  fallback for local dev/testing; see ADR-001.
- **Statements API (Application & Domain Layer):**
    - OAuth2 Token Manager (acquisition, caching, and token refresh lifecycle).
    - `ApiAuditLogger` middleware (payload, header, status code, and latency capture to `api_audit_logs`).
    - `StatementService` domain logic (consuming `StatementsApiClientInterface` with caching and domain models).
    - Inbound Facade Controllers & route mappings for internal client consumers.
- **PayShap Request API** — not started (spec → DTOs → transport).
- **AVS** — not started.
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

## Guardrails in force (G1–G10, from the proposal)
G1 no source-doc mutation · G2 no DB write without approval · G3 `.env`/secrets protection ·
G4 requirement-completeness gate · G5 test-before-completion · G6 API-contract verification ·
G7 in-container execution · G8 logging-isolation (0 rows to `mysql_fingo_logs` in tests) ·
G9 code-review gate · G10 migration-approval gate.
