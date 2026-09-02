#!/bin/sh
set -e

cd /var/www/html

# SQLite — utwórz bazy jeśli nie istnieją
[ -f database/database.sqlite ] || touch database/database.sqlite
[ -f database/blog.sqlite ]     || touch database/blog.sqlite
chown www-data:www-data database/*.sqlite 2>/dev/null || true

# Generuj klucz jeśli APP_KEY jest pusty
if [ -z "${APP_KEY:-}" ]; then
    echo "⚠  APP_KEY nie jest ustawiony — generuję tymczasowy."
    php artisan key:generate --force --quiet
fi

# Symlink storage → public/storage
php artisan storage:link --quiet 2>/dev/null || true

# Migracje
php artisan migrate --force --quiet
php artisan migrate --force --database=blog --path=database/migrations/blog --quiet

# Dane startowe (opcjonalnie) — np. SEED_CLASS=Database\Seeders\FederationDemoSeeder
# Seedery używają updateOrCreate/firstOrCreate, więc bezpiecznie uruchamiać je przy każdym starcie.
if [ -n "${SEED_CLASS:-}" ]; then
    echo "→ Uruchamiam seeder: ${SEED_CLASS}"
    php artisan db:seed --class="${SEED_CLASS}" --force --quiet
fi

# Cache / optymalizacja
php artisan config:cache  --quiet
php artisan route:cache   --quiet
php artisan view:cache    --quiet
php artisan optimize      --quiet

echo "✓ FEER CMS gotowy."
exec php-fpm
