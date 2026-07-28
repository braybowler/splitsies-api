# splitsies-api

Laravel 13 REST API for **Splitsies** — a group trip expense-splitting app. Groups track trip expenses, split them among members, and get an end-of-trip settle-up report.

- **Architecture & conventions:** see [`CLAUDE.md`](./CLAUDE.md)
- **Design source of truth:** Obsidian — `splitsies-plan.md` + `Features/Splitsies/`

---

## Quick start (easiest — SQLite, no external services)

Requires **PHP 8.5** and **Composer**. Nothing else — no MySQL, no AWS.

```sh
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate        # creates the SQLite database automatically
php artisan serve
```

API is now live at **http://localhost:8000**.

That's it. The defaults in `.env.example` use SQLite for the database and the `log` mail driver, so you can run and develop everything — including the magic-link auth flow — without configuring anything external.

### Magic-link login in local dev

Local mail defaults to `MAIL_MAILER=log`, so magic-link emails aren't actually sent — they're written to:

```
storage/logs/laravel.log
```

Trigger a login, then grab the link from the bottom of that log. No SES account needed to develop auth.

Tip: `php artisan pail` tails the logs live in a readable format.

---

## Running everything at once (optional)

```sh
composer dev
```

Runs the dev server, queue worker, and live log viewer together (via `concurrently`).

## Tests & formatting

```sh
composer test            # or: php artisan test
./vendor/bin/pint        # format code (Laravel Pint)
```

---

## Switching to MySQL (matches production)

Production uses **MySQL 8**. To develop against MySQL instead of SQLite, set these in `.env` and re-run `php artisan migrate`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=splitsies
DB_USERNAME=root
DB_PASSWORD=
```

## Production services (not needed for local dev)

- **AWS SES** for magic-link email (`MAIL_MAILER=ses`). ⚠️ Request SES production access early — sandbox only sends to verified addresses, and email *is* the auth channel.
- **AWS S3** for receipt-photo storage (signed URLs).
- **Frankfurter** (`frankfurter.dev`, no key) + `open.er-api.com` fallback for FX rates.

See [`CLAUDE.md`](./CLAUDE.md) for the full list of environment variables and conventions.
