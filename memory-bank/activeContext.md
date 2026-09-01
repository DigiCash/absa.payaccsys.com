# Active Context — ABSA API Hub

> Current focus, recent changes, next steps, open decisions. Update after significant changes.
> Last updated: 2026-09-01.

## Current focus
**Statements API — HTTP transport layer.** DTO layer is built; transport is mid-flight.
- `StatementsApiClientInterface`, `StatementsApiException`, `StatementsApiClientConfig` — **done**.
- `StatementsApiClient` (concrete, `Http::` facade) — **done through M4**.
- **M5 (mTLS + retry)** and **M6 (sign-off)** — **pending**.

## Recent changes (per planning docs; verify before relying on)
- 2026-08-26: `dto-plan.md` approved; DTOs implemented under `app/DTOs/StatementsAPI/` (~40 files).
- 2026-08-27: Transport M0–M4 completed. `StatementsApiClientTest` reported 16/16 (106 assertions)
  after M4 (8 happy-path + 8 error-path). Full Pest suite reported 56/56 (374 assertions) at M3.
- Migrations added: `api_audit_logs` + `add_environment_to_api_audit_logs_table`.
- Git: 4 commits; latest `1c2dfb9 "added .clineignore file to the list"`.

## Next steps (ordered)
1. **M5:** mTLS + retry — confirm `withOptions()` key set (D3), add SSL-options + retry-on-5xx/429 tests.
2. **M6:** sign-off — full `pest` green, `php -l` on all new files, update planning docs.
3. Resolve **D1 (auth)** before/around M5: send `api_key` as `Authorization: Bearer {api_key}`
   directly, or via a token endpoint? (Currently the client sends `Authorization: Bearer {apiKey}`.)
4. After Statements sign-off: begin **PayShap** then **AVS** (each: spec → DTOs → transport).
5. Create `Planning/99_Decisions/` ADRs to record D1/D3 resolutions.

## Open decisions / blockers
- **D1 (auth):** Bearer `api_key` vs token endpoint — **OPEN** (affects config/`apiKey` seam).
- **D3 (TLS keys):** Guzzle `cert`/`ssl_key` vs Laravel-curl `curl_ssl_cert`/`curl_ssl_key` — **OPEN** (blocks M5).
- **D2 (structure):** RESOLVED — concrete transport stays under `App\DTOs\StatementsAPI\Transport\`;
  test stays at `tests/Unit/Services/StatementsAPI/`.
- No CI pipeline exists; gates (G1–G10) enforced by agent + manual review until CI is added.

## Patterns & preferences to honour
- `declare(strict_types=1)` + `final readonly class` DTOs; native string-backed enums.
- Exact OpenAPI key casing in `toArray()` (e.g. `CreditDebitIndicator`, `FileCreationDateTime`).
- Namespaced enum values (`ZA.ABSA.*`) → safe case names, raw string as backing value.
- `CurrencyCode` stays a value DTO (ISO-4217), NOT an enum (documented exception).
- Hermetic unit tests: zero DB/network; use `Http::fake()` for transport.
- All CLI runs inside the `absa84_api` container (PHP 8.4), never host (PHP 8.2.31).

## Learnings / insights
- No DTO/enum library is installed (no Spatie/Invictus) — DTOs are native PHP, zero new deps.
- `config('absa.statements')` is the config seam (not bare `config('absa')`).
- `retry_attempts`/`retry_delay_ms` were deferred from M1 to M2/M5.
- `.agents/skills/` tree is inconsistent/partially broken — remediation required before Phase 1 QA role.
