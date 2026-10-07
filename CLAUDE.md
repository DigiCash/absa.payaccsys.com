<laravel-boost-guidelines>
=== .ai/00-project-bootstrap rules ===

# ABSA API — Project Bootstrap Guidelines

## Purpose

These guidelines define the behaviour required during the initial project discovery and agentic-development planning phase.

The project is a Laravel 13 API application running in Docker.

The application is intended to act as a central API hub for ABSA-related API capabilities.

The initial API Library services are:

1. Statements & Transactions API
2. PayShap Request API
3. Account Verification Service (AVS)

This phase is concerned with project discovery and planning.

It is NOT an implementation phase.

---

# 1. Bootstrap Phase Objective

The objective of the bootstrap phase is to understand the existing project and propose an appropriate engineering framework for AI-assisted development.

The agent must investigate the existing repository before proposing:

* project structure
* architectural conventions
* agent roles
* engineering skills
* workflows
* hooks
* quality gates
* planning documents
* development rules

The result should be a proposed structure for:

* `.ai/guidelines/`
* `Planning/`
* API-specific planning documentation
* engineering roles
* development workflows

The proposal must be based on the actual repository rather than assumptions.

---

# 2. Bootstrap Phase Restrictions

During the bootstrap/discovery phase, the agent MUST NOT:

* modify application source code
* modify routes
* modify controllers
* modify services
* modify models
* modify migrations
* modify database schemas
* modify `.env`
* modify Docker configuration
* modify Composer dependencies
* install packages
* uninstall packages
* change Laravel configuration
* execute database write operations
* execute destructive database operations
* call external ABSA APIs
* create production credentials
* change authentication configuration
* change logging configuration

The agent may inspect files and configuration.

The agent may inspect available database schemas and metadata using the available read-only database tooling.

The agent must treat database access as read-only unless the developer explicitly authorises a write operation.

---

# 3. Existing Technology Context

The project currently uses:

* Laravel 13
* PHP
* Docker
* Pest
* Laravel PAO
* `laravel-payaccsys-db-logger`
* MySQL 8.0.42 for central logging
* PostgreSQL for the application database
* Cline for AI-assisted development

The project also has MCP database access.

The available database connections are:

## mysql-monolith

Purpose:

Central logging database.

Database:

`logging`

Table of particular relevance:

`logs`

Database engine:

MySQL

Version:

8.0.42

Access:

READ ONLY during normal agent operation.

## postgres-switch-main

Purpose:

Main application database.

Database:

`absa_api`

Access:

READ ONLY during planning and discovery unless explicitly authorised otherwise.

---

# 4. Existing Packages

The project has already installed or configured the following relevant dependencies:

* `pestphp/pest`
* `laravel/pao`
* `laravel-payaccsys-db-logger`

The agent must inspect the actual `composer.json`, `composer.lock`, Laravel configuration, and installed package structure rather than relying solely on this summary.

---

# 5. Logging

The application uses:

`LOG_CHANNEL=log_stack`

Logging configuration has already been established.

The custom database logger package is responsible for integration with the central logging infrastructure.

The agent must inspect the existing logging implementation before proposing changes to logging architecture.

Do not replace, redesign, or modify the logging implementation during bootstrap.

Determine how the logger currently integrates with Laravel and document relevant findings.

---

# 6. API Source Documents

The project has source documentation for three API Library capabilities.

## Statements & Transactions API

Available source documents include:

* `Statements_Facade_API_Swagger_20250910.yml`
* `API Library - Statements API.pdf`

## PayShap Request API

Available source document:

* `API Library - PayShap Request_V5.pdf`

## Account Verification Service

Available source document:

* `API Library - Account Verification Service v3.docx`

These documents are source material.

They must not be modified.

During discovery, determine:

* what each document contains
* whether multiple documents describe the same API
* whether documents conflict
* what information is missing
* which documents appear authoritative for which requirements
* what information must be extracted before implementation

Do not implement API functionality during this phase.

---

# 7. Repository Discovery

Inspect the repository and determine:

* Laravel version
* PHP version
* Composer dependencies
* application structure
* routes
* configuration
* environment configuration structure
* database connections
* logging configuration
* Docker configuration
* testing configuration
* Pest configuration
* existing application code
* installed packages relevant to the project
* existing architectural conventions
* existing coding conventions
* existing exception handling
* existing HTTP client usage
* existing authentication mechanisms
* existing API response conventions
* existing validation conventions

Do not assume that Laravel's default structure is still unchanged.

Document deviations from the Laravel defaults.

---

# 8. Database Discovery

Using read-only access where available, investigate:

### PostgreSQL

`absa_api`

Determine:

* relevant schemas
* tables
* relationships
* indexes
* existing application data structures
* naming conventions

### MySQL

`logging`

Determine:

* logging schema
* `logs` table structure
* relevant columns
* indexes
* relationships if applicable

Do not modify either database.

Do not insert, update, delete, truncate, alter, migrate, or otherwise mutate database state.

---

# 9. Agentic Development Framework

After repository discovery, propose an appropriate set of engineering roles/skills.

Do not create roles merely because they are common in software development.

Each proposed role must have a clear responsibility within this project.

Consider roles such as:

* Requirements/API Analyst
* Senior PHP/Laravel Developer
* API Architect
* Integration Specialist
* Database Engineer
* QA/Test Engineer
* Security Engineer
* Code Reviewer
* Technical Writer

Determine whether each is actually useful.

For each proposed role, define:

* purpose
* responsibilities
* authority
* restrictions
* inputs
* outputs
* required knowledge
* hand-offs
* quality gates
* when the role should be invoked

---

# 10. Workflow Design

Propose workflows for at least:

* project discovery
* requirements analysis
* API analysis
* architecture/design
* implementation
* testing
* code review
* security review
* API changes
* defect resolution
* release readiness

Workflows should identify:

1. Entry conditions
2. Responsible role
3. Required inputs
4. Activities
5. Expected outputs
6. Validation/quality gates
7. Handoff to the next role
8. Conditions requiring developer intervention

---

# 11. Hooks and Guardrails

Identify useful agentic-development hooks and guardrails.

Examples include:

* pre-implementation checks
* requirement completeness checks
* test-before-completion checks
* security review checks
* database mutation protection
* source-document protection
* `.env` protection
* dependency-change approval
* migration approval
* API contract verification
* code-review gates

Do not implement hooks that require unsupported tooling.

First determine what Cline and the current project tooling can actually support.

---

# 12. Planning Structure

Propose a maintainable structure for:

`Planning/`

The structure should support:

* project context
* architecture
* agent roles
* workflows
* API-specific planning
* database documentation
* quality strategy
* architectural decisions

API-specific planning should allow each API Library service to maintain its own:

* requirements
* endpoint inventory
* request models
* response models
* mappings
* error behaviour
* open questions
* implementation plan
* testing strategy

---

# 13. Guidelines Structure

Propose which rules belong under:

`.ai/guidelines/`

Guidelines should contain persistent engineering rules rather than temporary task-specific information.

Consider rules for:

* project context
* architecture
* Laravel/PHP
* API development
* database access
* testing
* logging
* security
* AI-agent behaviour

Avoid duplicating large amounts of information between `Planning/` and `.ai/guidelines/`.

---

# 14. Source of Truth Hierarchy

When information conflicts, establish and document a hierarchy of authority.

At minimum, distinguish between:

1. Explicit developer instructions
2. Approved architectural decisions
3. Approved project requirements
4. Official API source documentation
5. Existing application behaviour
6. Database evidence
7. Agent assumptions

Agents must never silently promote an assumption into a requirement.

---

# 15. Ambiguity Handling

When information is unclear:

* identify the ambiguity
* document it
* investigate available evidence
* do not invent behaviour
* do not implement unresolved business decisions
* create an explicit question for developer clarification where necessary

Unresolved questions should eventually be maintained under the relevant API's planning directory.

---

# 16. Completion Criteria for Bootstrap

The bootstrap phase is complete only when the agent has produced a planning proposal containing:

* recommended Planning directory structure
* recommended `.ai/guidelines/` structure
* proposed engineering roles
* responsibilities for each role
* proposed workflows
* proposed hooks/guardrails
* role hand-offs
* quality gates
* database access rules
* source-document rules
* unresolved architectural questions
* identified project risks
* identified information gaps

The agent must NOT begin implementing application functionality.

At the end of the bootstrap phase, stop and present the proposal for developer review.

Do not automatically proceed from planning into implementation.

=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application running on PHP 8.4. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists (settled decisions, non-obvious traps, standing constraints). Framework and package guidelines that only apply to specific paths (testing, frontend, components) also live there, under `.ai/rules/boost` — this is not just recorded decisions, it is load-bearing guidance you have not seen inline. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.
- Record durable rules with `record-rule` so the next agent or teammate inherits them instead of working them out again. Pass a `glob` (e.g. `app/Http/Controllers/**`), a short `title`, and a few-line `note`. Always use `record-rule`, never your native memory or notes tool — native memory is personal and session-scoped; only `.ai/rules` is shared with the team and persists in the repo.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Follow existing application Enum naming conventions.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Test every code change by adding or updating a test.
- Run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

# Pest

- This project uses Pest. Create tests with `php artisan make:test --pest {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.
- Do not delete tests or test files without approval. They are part of the application.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/pest` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.
- After the feature tests pass, ask the user to run the complete suite with `php artisan test --compact`.

</laravel-boost-guidelines>
