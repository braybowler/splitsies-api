# splitsies-api

Laravel 13 REST API for **Splitsies** — a group trip expense-splitting app. Groups track trip expenses, split them among members, and get an end-of-trip settle-up report.

- **Architecture & conventions:** see [`CLAUDE.md`](./CLAUDE.md)
- **Design source of truth:** Obsidian — `splitsies-plan.md` + `Features/Splitsies/`

---

## Quick start (Laravel Sail — recommended)

Local dev runs in Docker via [Laravel Sail](https://laravel.com/docs/sail): PHP + **MySQL 8** + **Mailpit**, no local PHP/MySQL setup required beyond **Docker**.

```sh
cp .env.example .env
composer install                 # needs PHP + Composer locally; see note below if you don't have them
php artisan key:generate
./vendor/bin/sail up -d           # start PHP, MySQL, Mailpit
./vendor/bin/sail artisan migrate # create the schema in MySQL
```

You now have:

| Service           | URL                                              |
| ----------------- | ------------------------------------------------ |
| API               | http://localhost:8000                            |
| Mailpit (inbox UI)| http://localhost:8025                            |

Stop everything with `./vendor/bin/sail down` (add `-v` to also wipe the MySQL volume).

### Magic-link login in local dev

Outbound mail is caught by **Mailpit** — nothing leaves your machine. Trigger a login, then open **http://localhost:8025** to read the email and click the magic link. No AWS/SES needed to develop auth.

### Handy: a `sail` alias

Add to your shell profile so you can type `sail …` instead of `./vendor/bin/sail …`:

```sh
alias sail='sh $([ -f sail ] && echo sail || echo vendor/bin/sail)'
```

### No PHP/Composer locally?

Run the initial `composer install` through a throwaway container, then continue with the Sail commands above:

```sh
docker run --rm -v "$(pwd):/opt" -w /opt laravelsail/php85-composer:latest composer install --ignore-platform-reqs
```

---

## Common commands (via Sail)

```sh
sail up -d            # start containers
sail down             # stop containers
sail artisan migrate  # run migrations
sail artisan test     # run tests (against the MySQL "testing" database)
sail pint             # format code (Laravel Pint)
sail artisan pail     # live, readable log tail
sail mysql            # open a MySQL shell
```

> Tests run against MySQL (not SQLite) — the stack must be up (`sail up -d`) before `sail artisan test`. This keeps tests on the same engine as production.

---

## Environment

- **Database:** MySQL 8 (`mysql` service). Credentials in `.env` default to Sail's `sail` / `password`, database `splitsies`.
- **Mail:** Mailpit locally; **AWS SES** in production (`MAIL_MAILER=ses`). ⚠️ Request SES production access early — sandbox only sends to verified addresses, and email *is* the auth channel.
- **Storage:** **AWS S3** for receipt photos (signed URLs) in production.
- **FX rates:** **Frankfurter** (`frankfurter.dev`, no key) + `open.er-api.com` fallback.

See [`CLAUDE.md`](./CLAUDE.md) for the full environment variable list and conventions.
