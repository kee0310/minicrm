# MiniCRM (Laravel + Inertia + React)

A lightweight CRM built on Laravel, Inertia, and React (TSX) with user management, leads, deals, commissions, and settings UI.

## 🚀 Quick start

1. Clone repo:
    ```bash
    git clone <your-repo-url> minicrm
    cd minicrm
    ```
2. Install PHP dependencies:
    ```bash
    composer install
    ```
3. Install frontend dependencies:
    ```bash
    npm install
    ```
4. Copy env:
    ```bash
    cp .env.example .env
    ```
5. Set DB and other variables in `.env`:
    - `DB_CONNECTION=mysql`
    - `DB_HOST=...`
    - `DB_PORT=3306`
    - `DB_DATABASE=minicrm`
    - `DB_USERNAME=...`
    - `DB_PASSWORD=...`
    - `APP_KEY=` generated below

6. Generate app key:
    ```bash
    php artisan key:generate
    ```
7. Run migrations:
    ```bash
    php artisan migrate
    ```
8. Build assets (dev):
    ```bash
    npm run dev
    ```
    or production:
    ```bash
    npm run build
    ```
9. Serve:
    ```bash
    php artisan serve
    ```

## 🧩 What we changed (appearance)

- `routes/settings.php` now only has profile/password/two-factor routes.
- `resources/js/layouts/settings/layout.tsx` removed Appearance item.
- `resources/views/app.blade.php` disabled dark-mode class toggling.
- `resources/js/hooks/use-appearance.tsx` now forces light mode (`appearance='light'`).
- Removed page `resources/js/pages/settings/appearance.tsx` and route helper `resources/js/routes/appearance`.
- Removed `HandleAppearance` middleware reference in `bootstrap/app.php` if not needed.

## 📁 Key paths

- `app/Http/Controllers/Settings/` controllers
- `routes/settings.php` settings routes
- `resources/js/pages/settings/` settings pages
- `resources/js/hooks/use-appearance.tsx` theme logic
- `resources/views/app.blade.php` head-level theme script

## 🧪 Tests

- PHP tests: `php artisan test`
- JS checks: `npm run lint`

## ⚙️ Deploy notes

- Ensure `.env DB_*` points to host MySQL
- `php artisan config:clear`, `cache:clear`, `config:cache` after env changes
- `APP_DEBUG=false` in production

## 💡 Troubleshooting

- `SQLSTATE[HY000] [2002] Connection refused`: set correct DB host/port/user in `.env`
- `404 /settings/appearance`: route removed deliberately

---

Happy CRM building. 🎉
