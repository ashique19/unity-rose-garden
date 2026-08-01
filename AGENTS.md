# AGENTS.md

## Cursor Cloud specific instructions

Unity Rose Garden is a single **Laravel 13 / PHP 8.4** monolith (apartment billing + building accounts, timezone `Asia/Dhaka`). There is one web service; the `android/` Capacitor wrapper is optional and not needed to run/test the web app.

### Running the app
- Dev server: `php artisan serve --host=0.0.0.0 --port=8000` → app at `http://127.0.0.1:8000`.
- Full dev stack (server + queue worker + log tail + vite) is `composer dev`, but the queue/vite processes are optional for normal web flows.
- Seeded admin login (phone-based) at `/login`: phone `01785636359`, password `1289` (admin role). Public flat/statement pages need no login.

### Database
- Dev uses **SQLite** at `database/database.sqlite` (default `DB_CONNECTION=sqlite` in `.env`). The file, `.env`, and app key are created during environment setup and persist in the VM snapshot; they are gitignored, so a fresh `.env` is only needed if the snapshot is rebuilt.
- New migrations pulled from git are **not** auto-run by the startup update script. Run them yourself: `php artisan migrate --force` (or `php artisan migrate:fresh --seed` to reset + reseed).
- `production_database.sql`, `start.sh`, and the bundled `public/phpmyadmin/` target a MySQL/MariaDB setup, but they are legacy artifacts — the seeders are Eloquent-based and DB-agnostic, so SQLite is the supported dev backend.

### Tests & lint
- Tests: `php artisan test` (94 tests; uses in-memory SQLite from `phpunit.xml`).
- Lint: `./vendor/bin/pint` to fix, `./vendor/bin/pint --test` to check. Pint currently flags many **pre-existing** style deviations across the repo; do not treat that as a regression from your change.

### Gotchas
- Frontend assets are loaded via **CDN** (Bootstrap, Tailwind, Alpine) in `resources/views/layouts/layout.blade.php`; no view uses `@vite`. `npm run build` currently **fails** because `resources/css/app.css` (referenced in `vite.config.js`) is missing from the repo. This does not affect running the web app — do not block on it.
- Interactive artisan commands (`php artisan tinker`, `php artisan db`) fail with `TTY mode requires /dev/tty`. Use the non-interactive form, e.g. `php artisan tinker --execute="..."`.
- Optional integrations degrade gracefully without credentials: Gemini meter OCR (`GEMINI_API_KEY`) and Firebase push (`FCM_PROJECT_ID` / `FCM_SERVICE_ACCOUNT`).
