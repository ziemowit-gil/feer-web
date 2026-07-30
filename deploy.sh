#!/usr/bin/env bash
#
# Deploy FEER-web na serwerze produkcyjnym.
#
# Pobiera zmiany z gita, zachowując realną bazę SQLite (jest w .gitignore,
# ale w starym checkoucie serwera bywa jeszcze śledzona), uruchamia migracje
# i czyści cache. Bezpieczny do wielokrotnego uruchamiania.
#
# Użycie:
#   ./deploy.sh
#
# Zmienne środowiskowe (opcjonalne):
#   PHP_BIN   binarka PHP CLI            (domyślnie: php85)
#   BRANCH    gałąź do pobrania          (domyślnie: main)
#
# Przykład lokalnego testu:  PHP_BIN=php ./deploy.sh
set -euo pipefail

PHP_BIN="${PHP_BIN:-php85}"
BRANCH="${BRANCH:-main}"
DB="database/database.sqlite"

# Uruchamiaj z katalogu projektu (tam, gdzie leży ten skrypt).
cd "$(dirname "$0")"

echo "▶ Deploy FEER-web (gałąź: $BRANCH, PHP: $PHP_BIN)"

# 1. Kopia zapasowa realnej bazy (jeśli istnieje).
BACKUP=""
if [ -f "$DB" ]; then
    BACKUP="/tmp/feer-db-backup-$(date +%Y%m%d-%H%M%S).sqlite"
    cp "$DB" "$BACKUP"
    echo "  ✔ Kopia bazy: $BACKUP"
fi

# 2. Cofnij lokalne zmiany w pliku bazy, gdyby był jeszcze śledzony
#    (inaczej git pull odmówi). Gdy plik jest już ignorowany — nic nie robi.
git checkout -- "$DB" 2>/dev/null || true

# 3. Pobierz kod.
echo "▶ git pull origin $BRANCH"
git pull origin "$BRANCH"

# 4. Przywróć realną bazę na miejsce.
if [ -n "$BACKUP" ]; then
    cp "$BACKUP" "$DB"
    echo "  ✔ Przywrócono bazę z kopii"
fi

# 5. Migracje i czyszczenie cache.
echo "▶ Migracje i cache"
"$PHP_BIN" artisan migrate --force
"$PHP_BIN" artisan route:clear
"$PHP_BIN" artisan view:clear
"$PHP_BIN" artisan cache:clear

echo "✅ Deploy zakończony."
