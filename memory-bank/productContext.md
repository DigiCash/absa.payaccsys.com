# Product Context — ABSA API Hub

## Why this project exists
Fingo/Phoenix needs a single, stable, typed gateway to ABSA's banking APIs. Rather than each
consumer integrating ABSA directly (TLS, auth, error shapes, pagination), this hub centralises
that integration and exposes a clean internal contract.

## Problems it solves
- **Integration complexity:** ABSA APIs use mutual TLS, per-request correlation headers, and a
  shared `{ Data, Links, Meta }` response envelope. The hub hides this behind typed DTOs + a
  transport client.
- **Consistency:** One error model (`ErrorResponseDTO`), one envelope shape, one auth seam.
- **Auditability:** Operational logging (MySQL) and audit logging (PostgreSQL) are separated,
  with PII/secrets masked.
- **Maintainability:** Domain isolation means adding PayShap/AVS later does not destabilise
  Statements.

## How it should work (intended flow)
1. Internal consumer calls the hub (e.g. `POST /v1/login` → Sanctum token; future domain routes).
2. Hub builds a typed `*RequestDTO` (path params, query, ABSA headers, auth token).
3. `StatementsApiClient` (transport) issues the outbound HTTP call via `Illuminate\Http\Client`.
4. A `200` body is decoded into a typed `*ResponseDTO`; any non-2xx becomes a
   `StatementsApiException` carrying a decoded `ErrorResponseDTO`.
5. Callers depend on `StatementsApiClientInterface`, never on Guzzle/HttpClient directly.

## User / consumer experience goals
- **Typed, predictable** responses (exact OpenAPI key casing, e.g. `CreditDebitIndicator`).
- **Fail loud & typed** on errors (status + structured error payload).
- **Hermetic, fast** unit tests (zero DB/network) so changes are safe and quick.
- **Traceable** every internal route/DTO back to a normalised spec + source reference.

## Cross-cutting UX/quality expectations
- Unified JSON envelope; market scoping; API versioning (`/v1`).
- No plaintext PII/secrets in logs or tests.
- Auth changes and DB writes require explicit developer approval.
