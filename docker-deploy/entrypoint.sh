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

# Cache / optymalizacja
php artisan config:cache  --quiet
php artisan route:cache   --quiet
php artisan view:cache    --quiet
php artisan optimize      --quiet

echo "✓ FEER CMS gotowy."
exec php-fpm
