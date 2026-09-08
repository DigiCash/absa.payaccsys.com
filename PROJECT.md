# ABSA API

## Project Overview

ABSA API is a Laravel 13 API application that acts as a central API hub
for ABSA-related services.

The application is intended to expose a unified application boundary while
integrating with multiple ABSA API services.

The initial integrations are:

1. Statements & Transactions API
2. PayShap Request API
3. Account Verification Service (AVS)

The application is being developed as a modular Laravel monolith rather than
as separate Laravel applications for each ABSA service.

---

## Project Status

**Current phase:** Early implementation — Statements & Transactions API

**Implementation status:** The Statements API integration is implemented
across the DTO, transport and application layers and is covered by the Pest
test suite (~116 tests / ~627 assertions, all hermetic). PayShap and AVS are
planned but not yet started.

**Current development focus:** Completing and hardening the Statements API
integration, then onboarding the PayShap and AVS integrations using the same
DTO-first, domain-isolated pattern.

No API implementation should be considered approved merely because it has
been proposed in Planning documentation.

---

## Application Boundary

### This application is responsible for

- Providing the application's API boundary for ABSA-related functionality.
- Validating and processing incoming API requests.
- Applying application and business rules.
- Integrating with external ABSA API services.
- Translating between the application's internal models/contracts and
  external ABSA API contracts.
- Handling authentication and authorization as defined by the approved
  architecture.
- Handling integration failures, retries, timeouts and idempotency where
  required by the approved specifications.
- Providing appropriate logging and observability.
- Persisting application/domain data where persistence is required by the
  approved design.
- Providing automated tests for application behaviour and integrations.

### This application is not responsible for

- Reimplementing ABSA's external systems.
- Treating external ABSA payloads as the application's internal domain model
  without an explicit architectural decision.
- Treating the logging database as the application's domain database.
- Inventing undocumented ABSA API behaviour.
- Making assumptions about external API requirements where the supplied
  documentation is ambiguous or incomplete.

---

## Initial API Integrations

### Statements & Transactions API

Source material:

- Statements API Swagger specification
- Statements API PDF

Planning location:

`Planning/03_APIS/STATEMENTS/`

Approved documentation:

`docs/apis/statements/`

Implementation status:

- DTO layer (requests, responses, models, enums, error model) — **done**
- Transport layer (`StatementsApiClient` + config + typed exception, mTLS,
  retry-on-5xx/429) — **done**
- Application layer (`OAuth2TokenManager`, `ApiAuditLogger`, `StatementService`,
  inbound facade controllers) — **done**
- Inbound HTTP facade exposed under `/api/v1/statements/*` (Sanctum-protected)

---

### PayShap Request API

Source material:

- PayShap Request API PDF

Planning location:

`Planning/03_APIS/PAYSHAP/`

Approved documentation:

`docs/apis/payshap/`

---

### Account Verification Service (AVS)

Source material:

- Account Verification Service v3 Word document

Planning location:

`Planning/03_APIS/AVS/`

Approved documentation:

`docs/apis/avs/`

---

## Application Structure

The Statements integration is organised into layers under dedicated
namespaces. Each future ABSA domain (PayShap, AVS) will mirror this layout in
its own namespace rather than sharing unproven abstractions.

| Layer | Location | Responsibility |
| --- | --- | --- |
| HTTP facade | `app/Http/Controllers/StatementsAPI/` | Thin inbound adapters over `StatementService` |
| Requests (validation) | `app/Http/Requests/StatementsAPI/` | Inbound validation for facade endpoints |
| Application services | `app/Services/StatementsAPI/` | `OAuth2TokenManager`, `StatementService`, `AuditLogSanitizer`, contracts |
| Middleware | `app/Http/Middleware/` | `ApiAuditLogger` (audit), `LogApiJourney` (request journey) |
| Jobs | `app/Jobs/StatementsAPI/` | `RecordApiAuditLog` (queued audit persistence) |
| DTO layer | `app/DTOs/StatementsAPI/` | Typed requests, responses, models, enums, errors |
| Transport | `app/DTOs/StatementsAPI/Transport/` | `StatementsApiClient`, config value object, typed exceptions |

### Inbound facade routes

The Statements facade is registered in `routes/statements.php` and mounted
under the `api/v1` prefix, protected by `auth:sanctum`:

| Method | URI | Operation |
| --- | --- | --- |
| `GET` | `/api/v1/statements/health` | Health check |
| `GET` | `/api/v1/statements/balances` | Get balances (list) |
| `GET` | `/api/v1/statements/accounts/{accountId}/balances` | Get account balance |
| `GET` | `/api/v1/statements/` | Get all statements |
| `GET` | `/api/v1/statements/accounts/{accountId}/statements` | Get account statements |
| `GET` | `/api/v1/statements/accounts/{accountId}/statements/{statementId}` | Get statement |
| `GET` | `/api/v1/statements/accounts/{accountId}/statements/{statementId}/transactions` | Get statement transactions |
| `GET` | `/api/v1/statements/accounts/{accountId}/intraday-statement` | Get intraday statement |

Authenticated hub routes (`routes/api.php`): `POST /api/v1/login` issues a
Sanctum token, `GET /api/v1/user` returns the authenticated user.

### Postman Collection

`POSTMAN_COLLECTION/` contains an importable Postman workspace for exercising
the hub API. The folder is versioned (not git-ignored) so it travels with the
repository.

| File | Purpose |
| --- | --- |
| `ABSA API.postman_collection.json` | Requests for the AUTH flows and the Statements facade |
| `ABSA API LOCAL.postman_environment.json` | Local environment variables |

Collection layout:

- **AUTH** — `Login` (`POST`, stores the response `access_token` into the
  `ACCESS_TOKEN` environment variable) and `Check User` (`GET` the
  authenticated user).
- **StatementsAPI** — one `GET` request per inbound facade operation under
  `/api/v1/statements/*` (balances, statements, statement transactions,
  intraday statement).
- **Get System Health** — `GET` health check.

All Statements requests use the collection-level Bearer authentication
(`Authorization: Bearer {{ACCESS_TOKEN}}`), which the `Login` test script
refreshes automatically. Populate the LOCAL environment (`APP_URL`, the
`USER_*`/`USER_PASSWORD_*` credentials, and optionally the `BEARER_*` tokens)
before running.

> **Note:** the pre-existing `Login`, `Check User` and `Get System Health`
> request URLs omit the `api/v1` prefix (e.g. `{{APP_URL}}/login`), matching
> the collection as originally exported; they are intentionally left
> unchanged. The Statements facade requests use the full
> `/api/v1/statements/*` paths.

---

## Architecture

The intended architectural boundary is:

    API Consumer
         |
         v
    Laravel API
         |
         v
    Internal Application / Domain Model
         |
         v
    ABSA Integration Adapter
         |
         v
    External ABSA API

External API contracts must not automatically become internal
application/domain contracts.

The final architecture is subject to the decisions recorded in:

`Planning/02_ARCHITECTURE/`

and approved Architecture Decision Records in:

`docs/decisions/`

---

## Data Storage

The application uses multiple database systems with different responsibilities.

### PostgreSQL

**Connection:** `pgsql_main`

**Database:** `absa_api`

**Purpose:** Application and domain data.

### MySQL

**Connection:** `mysql_fingo_logs`

**Database:** Logging database

**Table:** `logs`

**Purpose:** Application logging through the custom database logger.

The logging database is infrastructure and must not be treated as the
application's domain database.

Database details and schema decisions are documented in:

`Planning/04_DATABASE/`

and, once approved:

`docs/database/`

---

## Logging

The application uses the project's configured `log_stack` logging channel.

The custom database logging path is currently understood to involve:

    InteractsWithDatabaseLog
        ->
    DatabaseLogProxy
        ->
    log_stack
        ->
    fingo_database_logger
        ->
    Payaccsys\DbLogger\DatabaseLogger
        ->
    mysql_fingo_logs.logs

The exact implementation and configuration must be verified against the
repository before being treated as authoritative architecture.

Logging rules and decisions are documented in:

`Planning/01_DISCOVERY/logging.md`

and:

`.clinerules/50-integrations.md`

---

## Testing

The project uses Pest for automated testing.

Tests must be designed to remain hermetic and must not depend unnecessarily
on external ABSA services or development infrastructure.

External HTTP integrations should be tested using appropriate HTTP fakes or
mocks.

Testing strategy and quality gates are documented in:

`Planning/05_TESTING/`

and, once approved:

`docs/architecture/testing.md`

---

## AI-Assisted Development

This project is developed with AI-assisted tooling, including Cline and a
local Qwen model.

AI development is governed by:

`.clinerules/`

The AI must not:

- invent API contracts;
- invent database schema;
- silently resolve contradictory requirements;
- make unapproved architectural decisions;
- expose secrets or sensitive data;
- make unrelated changes;
- treat assumptions as confirmed facts.

The AI should use the following navigation path:

    Task
      ->
    Applicable AI rules
      ->
    Approved specification
      ->
    Approved architecture
      ->
    Relevant source
      ->
    Relevant tests

---

## Source-of-Truth Principle

Information in this project has different levels of authority.

The preferred evidence hierarchy is:

1. Current source code and actual configuration.
2. Verified runtime database/schema information.
3. Repository migrations and configuration when runtime verification is
   unavailable.
4. Approved project documentation.
5. Architecture decisions and planning documents.
6. AI inference or assumptions.

Unknown information must remain explicitly unknown.

The AI must not convert an assumption or inference into an apparent fact.

---

## Project Documentation

### Planning

`Planning/`

Contains proposed, incomplete, disputed or awaiting-approval project work.

Planning documentation is not automatically authoritative.

### Approved Documentation

`docs/`

Contains approved and maintained project knowledge.

### AI Rules

`.clinerules/`

Contains persistent rules governing AI-assisted development.

### Architecture Decisions

`docs/decisions/`

Contains approved Architecture Decision Records.

---

## Development Constraints

- Laravel 13.
- PHP code must follow the project's approved coding standards.
- Changes must remain narrowly scoped.
- Architecture must be agreed before broad implementation.
- External API behaviour must be based on supplied/approved specifications.
- Sensitive information must not be written to logs.
- Database responsibilities must remain separated.
- Automated tests are required for implemented behaviour.
- Documentation must be updated when approved project behaviour or
  architecture changes.

---

## Current Planning Priorities

The current order of work is:

1. ~~Establish project and repository discovery.~~ — **done**
2. ~~Document infrastructure and database responsibilities.~~ — **done**
3. ~~Document the existing logging implementation.~~ — **done**
4. ~~Analyse the Statements API source material.~~ — **done**
5. Analyse the PayShap API source material.
6. Analyse the AVS source material.
7. ~~Establish the common API-hub architecture.~~ — **done** (ADRs recorded)
8. ~~Establish security and resilience requirements.~~ — **done** (ADR-001, ADR-003)
9. Establish persistence requirements.
10. ~~Establish testing strategy.~~ — **done** (hermetic Pest strategy in force)
11. ~~Record architectural decisions.~~ — **done** (ADR-001, ADR-003)
12. Promote approved planning into `docs/`.
13. Establish the final AI-agent rules.
14. Harden and extend the Statements integration; onboard PayShap, then AVS.

---

## Important Rule

**Do not start implementing an API simply because its source documentation
exists.**

Source documentation must first be analysed, normalized, reconciled where
necessary, and incorporated into an approved specification and architecture.

---

## Where To Start

A developer or AI agent entering the project should read:

1. `README.md` — quick start and Laravel-focused overview
2. `PROJECT.md` — this document: project scope and architecture
3. `memory-bank/` — current agent context, progress and patterns
4. `.clinerules/00-agent-workflow.md` — agent workflow guardrails
5. Relevant files under `Planning/`
6. Relevant approved documentation under `docs/`
7. Relevant source code
8. Relevant tests

Do not read the entire repository unless the task genuinely requires it.
