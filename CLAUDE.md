# CLAUDE.md — splitsies-api

Laravel 13 REST API (PHP 8.5, MySQL 8) — the backend for **Splitsies**, a group trip expense-splitting app.

> **Design source of truth lives in Obsidian**, not here. Read before doing feature work:
> - Master plan: `/Users/braydenbowler/Documents/Obsidian Vault/splitsies-plan.md`
> - Feature notes: `/Users/braydenbowler/Documents/Obsidian Vault/Features/Splitsies/`
>
> This file covers *how the codebase is built*; the Obsidian notes cover *what to build and why*.

## What Splitsies does

Groups track expenses over a trip, split them among members (equal-among-subset per line item, proportional tax/tip), and get an end-of-trip **simplified settle-up report** (who owes whom in the fewest transactions). Multi-currency with a per-trip base currency. See the plan for the full 16-decision design log.

## Architecture

Mirrors `wedge-matrix-api` / `tee-time-api`:

- **Service → Repository → Model**. Controllers are thin, single-action, and delegate to services. Services hold business logic; repositories own data access; models stay lean.
- **Single-action controllers** (`__invoke`) under `app/Http/Controllers`.
- **Form Requests** (`app/Http/Requests`) for validation.
- **API Resources** (`app/Http/Resources`) for response shaping.
- **DTOs** in `app/Data` for passing structured data between layers.
- **Contracts** (`app/Contracts`) for interfaces (e.g. FX client, repositories).
- **HTTP clients** (`app/Http/Clients`) for outbound integrations — here, the Frankfurter FX client (+ `open.er-api.com` fallback).
- **Auth: Sanctum bearer tokens**, issued after **magic-link** verification (no passwords). See the [[Auth & Accounts]] note.

### Domain-specific rules (from the design)

- **Money is stored as integer minor units.** Never floats. The currency's exponent (USD=2, JPY=0, KWD=3) determines scale.
- **Snapshots are authoritative.** Each expense stores derived cent-exact `expense_splits` (in the trip base currency) and its FX `exchange_rate`, computed at write time via **largest-remainder rounding**. Editing an expense re-runs the whole snapshot and atomically replaces its split rows.
- **Reconciliation invariants** (assert in tests): Σ an expense's splits == its total; Σ all participant net balances == 0.
- **Settle-up** = net balances → greedy minimal-transaction algorithm, deterministic (stable tie-break).

## Directory layout (`app/`)

```
Contracts/        interfaces
Data/             DTOs
Http/Clients/     outbound HTTP (Frankfurter FX)
Http/Controllers/ single-action controllers
Http/Requests/    form request validation
Http/Resources/   API resources
Repositories/     data access
Services/         business logic
Models/           Eloquent models
```

## Commands

```bash
composer dev        # serve + queue + logs + (no vite here; API only)
composer test       # php artisan test
php artisan test    # PHPUnit
./vendor/bin/pint   # format (Laravel Pint)
php artisan migrate
```

## Setup notes / TODO for first real work

- **DB:** currently the fresh-scaffold default (SQLite). Switch `.env` to **MySQL 8** per the plan when the DB is provisioned.
- **Email (magic links):** AWS SES. `MAIL_MAILER=ses`. ⚠️ Request SES production access early — sandbox only sends to verified addresses, and email *is* the auth channel.
- **Storage (receipt photos):** AWS S3, signed URLs, object key stored on the expense.
- **FX:** Frankfurter (`frankfurter.dev`, no key) + `open.er-api.com` fallback, fetched server-side as-of the expense date, cached in `fx_rates`.

## Deploy

Docker image → `ghcr.io`, push-to-`main` → DigitalOcean droplet (same pattern as the other APIs).
