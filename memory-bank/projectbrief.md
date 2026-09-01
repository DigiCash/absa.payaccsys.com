# Project Brief — ABSA API Hub (`absa84_api`)

> Foundation document. Source of truth for project scope. Read first, every session.
> Last updated: 2026-09-01. Phase: Bootstrap / Discovery (Phase 0) → early implementation of the Statements API.

## 1. What this project is
A **Laravel 13 API application** (PHP 8.4, running in Docker) that acts as a **central API hub**
for ABSA-related banking capabilities. It wraps external ABSA APIs behind a typed, internal
contract and exposes them to internal consumers (e.g. "Phoenix").

## 2. Core requirements / goals
- Integrate three ABSA API capabilities, in priority order:
  1. **Statements & Transactions API** (active — DTO + transport layer in progress)
  2. **PayShap Request API** (planned, not started)
  3. **Account Verification Service (AVS)** (planned, not started)
- Provide a **typed, DTO-first** internal contract (no raw arrays crossing domain boundaries).
- Keep each domain integration **isolated** in its own namespace; no premature cross-domain sharing.
- Maintain a **dual-database** design: application data (PostgreSQL) vs. audit/operational logging (MySQL).
- Be **AI-agent-friendly**: planning-before-implementation, traceable decisions, hermetic tests.

## 3. Non-goals (current phase)
- Do NOT invent business behaviour. Do NOT silently promote assumptions to requirements.
- Do NOT modify external ABSA source docs (read-only).
- Do NOT call live ABSA APIs or create production credentials during discovery.
- Do NOT add new dependencies unless genuinely required.

## 4. Source-of-truth hierarchy (binding — highest wins on conflict)
1. Explicit developer instructions
2. Approved architectural decisions (`Planning/99_Decisions/` ADRs — not yet created)
3. Approved project requirements (normalised specs in `Planning/03_APIS/`)
4. Official ABSA source documentation (`_StatementsAPI_/`, `_PaysShap_/`, `_AVS_/`, `_DOCUMENTATION_/`, `_SWAGGER_/`, `_CERTS_/`) — **read-only**
5. Existing application behaviour / code
6. Database evidence (read-only via MCP: `mysql-monolith`, `postgres-switch-main`)
7. Agent assumptions — **never promoted silently to requirements**

## 5. Scope boundaries
- **In scope now:** Statements API DTOs + HTTP transport layer; planning/governance docs.
- **Out of scope now:** PayShap, AVS, OAuth2/token acquisition, queue workers, CI pipeline (none exists).

## 6. Key governing documents
- `.ai/guidelines/00-project-bootstrap.md` — discovery-phase rules (governs Phase 0).
- `.clinerules/00..03` — agent workflow, environment, coding standards, hardware budget.
- `Planning/00_PROJECT_DISCOVERY_PROPOSAL.md` — the Phase 0 proposal (DRAFT, awaiting approval).
- `Planning/03_APIS/STATEMENTS/dto-plan.md` — **approved** DTO structure (2026-08-26).
- `Planning/03_APIS/STATEMENTS/client-transport-plan.md` — transport layer plan (M0–M4 done).
