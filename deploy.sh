#!/usr/bin/env bash
#
# Deploy FEER-web na serwerze produkcyjnym.
#
# Pobiera zmiany z gita, buduje assety, uruchamia migracje obu baz (główna
# i blog) oraz czyści cache. Włącza tryb serwisowy na czas wdrożenia i
# automatycznie go wyłącza — nawet w razie błędu.
#
# Użycie:
#   ./deploy.sh
#
# Zmienne środowiskowe (opcjonalne):
#   PHP_BIN       binarka PHP CLI                (domyślnie: php85 lub php84)
#   COMPOSER_BIN  binarka Composer               (domyślnie: composer)
#   NPM_BIN       binarka npm                    (domyślnie: /opt/alt/alt-nodejs20/root/usr/bin/npm)
#   BRANCH        gałąź do pobrania              (domyślnie: main)
#
# Przykład lokalnego testu:  PHP_BIN=php COMPOSER_BIN=composer NPM_BIN=npm ./deploy.sh
set -euo pipefail

# ─── Konfiguracja ─────────────────────────────────────────────────────────────

if [ -z "${PHP_BIN:-}" ]; then
    if php85 -v >/dev/null 2>&1; then
        PHP_BIN="php85"
    elif php84 -v >/dev/null 2>&1; then
        PHP_BIN="php84"
    else
        PHP_BIN="php"
    fi
fi
COMPOSER_BIN="${COMPOSER_BIN:-composer}"

# Jeśli composer nie jest dostępny w PATH, użyj pełnej ścieżki z php84
if ! command -v "$COMPOSER_BIN" >/dev/null 2>&1; then
    PHP_BIN="/opt/alt/php84/usr/bin/php"
    COMPOSER_BIN="/usr/local/bin/composer"
fi
NPM_BIN="${NPM_BIN:-/opt/alt/alt-nodejs20/root/usr/bin/npm}"
BRANCH="${BRANCH:-main}"
DB="database/database.sqlite"
BLOG_DB="database/blog.sqlite"

cd "$(dirname "$0")"

# ─── Nagłówek ─────────────────────────────────────────────────────────────────

echo ""
echo "┌─────────────────────────────────────────────────────────────┐"
printf "│  %-61s│\n" "Deploy FEER-web"
printf "│  %-61s│\n" "Gałąź: $BRANCH  |  PHP: $PHP_BIN"
echo "└─────────────────────────────────────────────────────────────┘"
echo ""

# ─── Podgląd nadchodzących commitów ───────────────────────────────────────────

echo "  Pobieranie informacji o zmianach..."
git fetch origin "$BRANCH" --quiet

INCOMING=$(git log --oneline --graph HEAD..origin/"$BRANCH" 2>/dev/null || true)

if [ -z "$INCOMING" ]; then
    echo ""
    echo "  ┌─ Brak nowych commitów na origin/$BRANCH ─────────────────┐"
    echo "  │  Lokalnie jest już najnowsza wersja.                    │"
    echo "  └──────────────────────────────────────────────────────────┘"
    echo ""
    printf "  Czy mimo to kontynuować deploy? [t/N] "
    read -r confirm
    [[ "$confirm" =~ ^[tTyY]$ ]] || { echo "  Anulowano."; exit 0; }
else
    COUNT=$(echo "$INCOMING" | grep -c "^\*" || true)
    echo ""
    echo "  ┌─ Commity do wdrożenia ($COUNT) ──────────────────────────────┐"
    while IFS= read -r line; do
        printf "  │  %-58s│\n" "$line"
    done <<< "$INCOMING"
    echo "  └──────────────────────────────────────────────────────────┘"
    echo ""
    printf "  Wdrożyć te zmiany? [t/N] "
    read -r confirm
    [[ "$confirm" =~ ^[tTyY]$ ]] || { echo "  Anulowano."; exit 0; }
fi

echo ""

# ─── Tryb serwisowy ───────────────────────────────────────────────────────────

echo "  [1/7] Tryb serwisowy: włączam..."
"$PHP_BIN" artisan down --render="errors::503" --retry=30 2>/dev/null || true

_przywroc_serwis() {
    echo ""
    echo "  !!! Błąd — wyłączam tryb serwisowy awaryjnie..."
    "$PHP_BIN" artisan up 2>/dev/null || true
}
trap _przywroc_serwis ERR

# ─── Kopia zapasowa baz SQLite ────────────────────────────────────────────────

TS=$(date +%Y%m%d-%H%M%S)
BACKUP=""
BLOG_BACKUP=""

echo "  [2/7] Kopia zapasowa baz..."
if [ -f "$DB" ]; then
    BACKUP="/tmp/feer-db-$TS.sqlite"
    cp "$DB" "$BACKUP"
    echo "        Główna:  $BACKUP"
fi
if [ -f "$BLOG_DB" ]; then
    BLOG_BACKUP="/tmp/feer-blog-db-$TS.sqlite"
    cp "$BLOG_DB" "$BLOG_BACKUP"
    echo "        Blog:    $BLOG_BACKUP"
fi

# ─── Git pull ─────────────────────────────────────────────────────────────────

echo "  [3/7] Pobieranie kodu..."
git checkout -- "$DB" 2>/dev/null || true
git checkout -- "$BLOG_DB" 2>/dev/null || true
git pull origin "$BRANCH"

if [ -n "$BACKUP" ]; then
    cp "$BACKUP" "$DB"
    echo "        Przywrócono bazę główną."
fi
if [ -n "$BLOG_BACKUP" ]; then
    cp "$BLOG_BACKUP" "$BLOG_DB"
    echo "        Przywrócono bazę bloga."
fi

# ─── Composer ─────────────────────────────────────────────────────────────────

echo "  [4/7] Composer install..."
COMPOSER_PATH="$(command -v "$COMPOSER_BIN" 2>/dev/null || echo "$COMPOSER_BIN")"
"$PHP_BIN" "$COMPOSER_PATH" update \
    --no-interaction \
    --no-dev \
    --optimize-autoloader \
    --quiet

# ─── Assety front-endowe ──────────────────────────────────────────────────────

echo "  [5/7] Budowanie assetów (npm)..."
export PATH="$(dirname "$NPM_BIN"):$PATH"
if [ -f package-lock.json ]; then
    "$NPM_BIN" ci --silent
else
    "$NPM_BIN" install --silent
fi
"$NPM_BIN" run build

# ─── Migracje ─────────────────────────────────────────────────────────────────

echo "  [6/7] Migracje..."
echo "        Baza główna..."
"$PHP_BIN" artisan migrate --force
echo "        Baza bloga..."
"$PHP_BIN" artisan migrate --force \
    --database=blog \
    --path=database/migrations/blog

# ─── Cache i optymalizacja ────────────────────────────────────────────────────

echo "  [7/7] Czyszczenie i optymalizacja cache..."
"$PHP_BIN" artisan optimize:clear --quiet
"$PHP_BIN" artisan optimize --quiet

# ─── Koniec ───────────────────────────────────────────────────────────────────

trap - ERR
"$PHP_BIN" artisan up

echo ""
echo "┌─────────────────────────────────────────────────────────────┐"
echo "│  Deploy zakończony pomyślnie.                               │"
echo "└─────────────────────────────────────────────────────────────┘"
echo ""
