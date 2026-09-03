# Tech Context — ABSA API Hub

> Technologies, dev setup, constraints, dependencies, tool usage. Last updated: 2026-09-03.

## 1. Stack
- **Framework:** Laravel `^13.17` (`laravel/framework` locked).
- **PHP:** `^8.4` required. **Docker base image `php:8.4-apache`** (container `absa84_api`).
  **Host interpreter is PHP 8.2.31** — do NOT run PHP on the host; always use the container.
- **Runtime:** Docker (build context `.../absa84_api`; code root `.../absa84_api/html`).
- **AI agent:** Cline. Local LLM: `qwen3.8:27b-mlx` via Ollama on 36GB unified RAM.

## 2. Testing & tooling
- **Pest** `^5.1` (Pest ≥ 3.0 satisfied), **`laravel/pao`** `^1.1` (required), `nunomaduro/collision ^8.6`,
  `phpunit/phpunit` (locked), `fakerphp/faker ^1.23`, `mockery/mockery ^1.6`.
- **`laravel/pint`** `^1.27` (formatter/linter), **`laravel/pail`** `^1.2.5` (log viewer).
- **NOT installed:** `laravel/boost` (README recommends it — open question Q-1); no Passport/JWT;
  no dedicated HTTP client (native `Illuminate\Http\Client` only); no queue-worker config beyond defaults.
- **Domain package:** `payaccsys/laravel-db-logger: dev-main` from private VCS
  `https://github.com/DigiCash/laravel-payaccsys-db-logger.git` (declared in composer `repositories`).
  `ext-pdo` is the only platform ext declared.

## 3. Databases (MCP, read-only by default)
- `postgres-switch-main` → db `absa_api` (application/audit).
- `mysql-monolith` → db `logging`, table `logs` (MySQL 8.0.42, central logging).
- `.env.example` default `DB_CONNECTION=sqlite` (local dev default; production uses the above).

## 4. Configuration
- `config/absa.php`: `environment` (`ABSA_ENV`, default `sandbox`), `statements` block
  (`base_url`, `api_key`, `client_id`, `client_secret`, `passphrase`, `cert_path`, `key_path`,
  `retry_attempts`=3, `retry_delay_ms`=250), `payshap`/`avs` placeholders.
- `config/db-logger.php` (db-logger), `config/logging.php` (`log_stack` channel), `config/sanctum.php`.
- Other config: `app`, `auth`, `cache`, `database`, `filesystems`, `mail`, `queue`, `services`, `session`.

## 5. Application structure (deviations from fresh Laravel 13 skeleton)
- Namespace `App\` → `app/` (PSR-4): `App\Http\Controllers`, `App\Models`, `App\Providers`,
  plus custom `App\Support\` and `App\Traits\`.
- Custom: `app/Support/DatabaseLogProxy.php`, `app/Traits/InteractsWithDatabaseLog.php`.
- **Application layer:** `App\Services\StatementsAPI\OAuth2TokenManager` +
      `Contracts\OAuth2TokenManagerInterface` — **DONE (2026-09-03)**. Still DRAFT:
      `App\Services\StatementsAPI\StatementService`, `App\Http\Middleware\ApiAuditLogger`,
      `App\Http\Controllers\StatementsAPI\` (Health/Balances/Statements/StatementTransactions).
- Migrations: `create_users_table`, `create_cache_table`, `create_jobs_table`,
  `create_personal_access_tokens_table`, `create_api_audit_logs`, `add_environment_to_api_audit_logs_table`.
- Routes (`routes/api.php`): `POST /v1/login` (Sanctum token), `GET /v1/user` (`auth:sanctum`).

## 6. Tool usage patterns (MANDATORY)
- **All CLI inside the container, no `-it`:** `docker exec absa84_api <command>`.
  - Syntax pre-flight: `docker exec absa84_api php -l <file>` (before completing any task).
  - Targeted tests only: `docker exec absa84_api ./vendor/bin/pest tests/Unit/DTOs/StatementsAPI/Support`.
  - Never run the full unconstrained suite during incremental edits.
- **Hermetic unit tests:** zero DB/network; `Http::fake()` for transport; concrete test doubles.
- **Batch limit:** ≤ ~10–15 files per turn; run `/newtask` between milestones to keep context lean.
- **No CI exists** — gates enforced by agent + manual review.

## 7. Constraints / gotchas
- Host PHP is 8.2.31 — running PHP on the host gives wrong results; always use the 8.4 container.
- `.env` is gitignored; never log secrets/PII in plaintext.
- External ABSA source docs are read-only; only `Planning/03_APIS/` is writable for derived specs.
- Context budget: do not load large binary docs (.pdf/.docx) or full raw dir listings into context.
- **Pest helper-function collision:** top-level `function` helpers in a test file are namespace-scoped;
  a duplicate name across two files in the same namespace (`Tests\Unit\Services\StatementsAPI`) triggers
  `Cannot redeclare function` when the full suite loads both. Keep helper names unique per namespace.
- **Laravel 13.25 removed `Cache::fake()`.** Hermetic cache tests use the test env's `CACHE_STORE=array`
  (phpunit.xml) for a fresh in-memory store per test.
- **`Request::body()` (not `Request::content()`)** asserts form-encoded request data under `Http::fake()`.
