# MikroTik Cards System — AGENTS.md

## Quick start
- `composer install` — dependencies
- `php artisan key:generate` — app key
- `php artisan migrate` — all tables
- `php artisan db:seed --class=InitialAdminSeeder` — creates admin (default: admin/admin123)
- `php artisan serve --host=0.0.0.0 --port=8000` — dev server
- `php artisan queue:work --queue=cards --tries=3 --timeout=60 --sleep=3` — **must run in separate terminal** for card generation to work

## Architecture
- **Laravel 12**, PHP 8.2+, MySQL (DB_CONNECTION=database for queue/cache/session too)
- RouterOS v6 integration via `evilfreelancer/routeros-api-php` on port 8728 (User Manager, **not** Hotspot)
- Jeeb wallet webhook via Android Emulator on same physical machine (localhost-only + API key)
- Three services with strict separation: `WebhookParser` (regex only), `MikroTikService` (network only), `CardGeneratorService` (orchestration + DB)
- Single queue job `GenerateMikrotikCardJob` on `cards` queue with exponential backoff [10, 30, 90]s, 3 tries
- `Cache::lock` prevents concurrent RouterOS API connections (lock key: `mikrotik_connection_lock`, TTL: 30s)

## Authentication
- **Two separate guards**: `web` (users table — Flutter app clients) and `admin` (admins table — admin panel)
- Admin guard uses session driver; custom middleware alias `admin.auth` protects all `/admin/*` routes
- Flutter API is **one-time registration only** (no login endpoint); matching is via `full_name + phone` against webhook payment notifications
- Sanctum is installed but not actively used for API auth

## Key middleware
- `admin.auth` → `App\Http\Middleware\AdminAuth` — session check on admin guard
- `localhost.only` → `App\Http\Middleware\LocalhostOnly` — IP must be 127.0.0.1/::1 + `X-Jeeb-Secret` header matches `JEEB_WEBHOOK_SECRET`
- Both registered via `bootstrap/app.php` aliases

## Important models & constraints
- `RawWebhook` — append-only audit log; `$guarded = ['*']`, `booted()` prevents UPDATE/DELETE, no `updated_at` column
- `RouterSetting` — singleton (id=1); `password` column uses Laravel `encrypted` cast (not hashed)
- `Admin` — uses `hashed` cast on password; `Authenticatable` contract with session guard
- `User` (Flutter clients) — not `Authenticatable`; no password/email fields; phone auto-normalized to `967XXXXXXXXX`
- `Transaction` — statuses: `pending_match`, `matched`, `processing`, `completed`, `failed`, `manual_pending`
- `username == password` for MikroTik User Manager cards (10-char alphanumeric, excludes 0/O/1/I/L for visual clarity)

## API routes
| Method | Route | Purpose |
|--------|-------|---------|
| POST | `/api/auth/register` | One-time client registration |
| GET | `/api/profiles` | List active packages |
| POST | `/api/purchase` | Create purchase request |
| POST | `/api/webhook/jeeb` | Receive payment notification (localhost only) |

## Webhook flow
1. Emulator sends POST to `/api/webhook/jeeb` (localhost + `X-Jeeb-Secret`)
2. `LocalhostOnly` middleware validates IP and secret key
3. Raw payload saved to `raw_webhooks` immediately (audit trail)
4. `WebhookParser` extracts amount, phone, name, reference via regex
5. If `full_name + phone` matches a registered `User`, looks for `pending_match` transaction
6. On match: transaction → `matched`, dispatches `GenerateMikrotikCardJob` on `cards` queue
7. No match or no pending transaction → `manual_pending` status (admin intervenes)

## Dev commands
- `php artisan queue:failed` — view failed jobs
- `php artisan queue:retry all` — retry all failed jobs
- `php artisan queue:flush` — clear failed jobs
- `php artisan tinker` — test router connection: `app(\App\Services\MikroTikService::class)->testConnection('host', 8728, 'user', 'pass')`
- `php artisan db:seed --class=InitialAdminSeeder` — first admin account

## Testing
- PHPUnit 11 is a dev dependency, but **no tests exist yet** (`tests/` directory exists, no test files)
- No Pint config file committed; runs with defaults

## Config files
- `config/jeeb.php` — webhook secret, emulator package, regex patterns for parsing
- `config/mikrotik.php` — port, timeout, lock key, User Manager API paths
- `config/auth.php` — defines `admin` guard/provider

## Quirks
- `QUEUE_CONNECTION=database`, `CACHE_STORE=database`, `SESSION_DRIVER=database` — all three use the MySQL database
- `APP_LOCALE=ar`, `APP_TIMEZONE=Asia/Aden`, `APP_FAKER_LOCALE=ar_SA`
- The webhook regex patterns in `config/jeeb.php` parse Arabic text from Jeeb wallet notifications
- No `.github/workflows/` — CI not configured
- Resources are Blade views (no frontend build pipeline); no Vite config committed
