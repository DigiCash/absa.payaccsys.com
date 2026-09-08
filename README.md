<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## ABSA API Hub

This repository is a **Laravel 13 API application** that acts as a central hub
for **ABSA** banking capabilities. It wraps external ABSA APIs behind a typed,
DTO-first internal contract and exposes a clean, authenticated HTTP boundary to
internal consumers.

> **This is not a bare Laravel skeleton.** It extends Laravel to integrate
> external ABSA API services. For the full project scope, architecture,
> governance rules and status, see **[PROJECT.md](PROJECT.md)**.

### What this project entails

- A **central API hub** for ABSA capabilities, built as a modular Laravel
  monolith (not one app per ABSA service).
- Three ABSA integrations, in priority order:
  1. **Statements & Transactions API** — implemented (DTO + transport +
     application layers, with Pest coverage).
  2. **PayShap Request API** — planned, not started.
  3. **Account Verification Service (AVS)** — planned, not started.
- A **typed, DTO-first internal contract** (requests, responses, models,
  enums, one shared error model) — no raw arrays crossing domain boundaries.
- **Domain-isolated namespaces** — each ABSA capability lives in its own
  namespace; adding PayShap/AVS later does not destabilise Statements.
- A **dual-database design** — application/audit data (PostgreSQL) separated
  from operational logging (MySQL).
- **AI-agent-friendly engineering** — planning-before-implementation,
  traceable decisions, hermetic tests.

### Placing this project in Laravel terms

It uses standard Laravel concepts to do all of the above:

- **Sanctum** for internal token authentication (`POST /api/v1/login`).
- **Controllers + Form Requests** for the inbound Statements facade
  (`routes/statements.php`, `/api/v1/statements/*`).
- **Queued jobs + middleware** for audit logging (`ApiAuditLogger`,
  `RecordApiAuditLog`).
- **HTTP client abstractions** for the outbound ABSA transport
  (`app/DTOs/StatementsAPI/Transport/`), with mTLS and retry-on-5xx/429.
- **Pest** for a fully hermetic test suite (no DB/network in unit tests).

## Getting Started

This repository is a standard Laravel application run inside Docker.

### Prerequisites

- Docker (the app targets PHP 8.4; the container must be used instead of any
  host PHP interpreter).

### Running the application

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --force
npm install
npm run build
php artisan dev
```

### Running the tests

The project uses [Pest](https://pestphp.com), run inside the container:

```bash
docker exec absa84_api ./vendor/bin/pest tests/
```

> Tests are designed to be hermetic — unit tests make zero database or
> network calls, and outbound HTTP is tested with fakes.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Project Documentation

- **[PROJECT.md](PROJECT.md)** — project scope, architecture, databases,
  governance and status.
- **[memory-bank](memory-bank/)** — current agent context, progress and
  engineering patterns.
- **[POSTMAN_COLLECTION](POSTMAN_COLLECTION/)** — Postman collection and LOCAL
  environment for exercising the AUTH flows and Statements facade endpoints.
- **[Planning](Planning/)** — proposed, in-progress and planned project work.
- **[docs](docs/)** — approved and maintained project knowledge.

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
