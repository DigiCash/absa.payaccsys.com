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

**Current phase:** Planning / Architecture

**Implementation status:** Initial Laravel application

**Current development focus:** API architecture, integration boundaries,
contracts, persistence, security, resilience, testing and AI-assisted
development preparation.

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

1. Establish project and repository discovery.
2. Document infrastructure and database responsibilities.
3. Document the existing logging implementation.
4. Analyse the Statements API source material.
5. Analyse the PayShap API source material.
6. Analyse the AVS source material.
7. Establish the common API-hub architecture.
8. Establish security and resilience requirements.
9. Establish persistence requirements.
10. Establish testing strategy.
11. Record architectural decisions.
12. Promote approved planning into `docs/`.
13. Establish the final Cline rules.
14. Begin focused implementation.

---

## Important Rule

**Do not start implementing an API simply because its source documentation
exists.**

Source documentation must first be analysed, normalized, reconciled where
necessary, and incorporated into an approved specification and architecture.

---

## Where To Start

A developer or AI agent entering the project should read:

1. `PROJECT.md`
2. `.clinerules/00-agent-workflow.md` — when available
3. Relevant files under `Planning/`
4. Relevant approved documentation under `docs/`
5. Relevant source code
6. Relevant tests

Do not read the entire repository unless the task genuinely requires it.
