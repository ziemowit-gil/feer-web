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
#   PHP_BIN       binarka PHP CLI                (domyślnie: php84)
#   COMPOSER_BIN  binarka Composer               (domyślnie: composer84)
#   NPM_BIN       binarka npm                    (domyślnie: /opt/alt/alt-nodejs20/root/usr/bin/npm)
#   BRANCH        gałąź do pobrania              (domyślnie: main)
#
# Przykład lokalnego testu:  PHP_BIN=php COMPOSER_BIN=composer84 NPM_BIN=npm ./deploy.sh
set -euo pipefail

# ─── Kolory ───────────────────────────────────────────────────────────────────

if [ -t 1 ]; then
    GRN=$'\033[0;32m'; YEL=$'\033[0;33m'; RED=$'\033[0;31m'
    CYN=$'\033[0;36m'; BLD=$'\033[1m';    DIM=$'\033[2m'; NC=$'\033[0m'
else
    GRN=''; YEL=''; RED=''; CYN=''; BLD=''; DIM=''; NC=''
fi

NSTEPS=7
ok()   { printf "    ${GRN}✓${NC}  %s\n" "$1"; }
warn() { printf "    ${YEL}⚠${NC}  %s\n" "$1"; }
fail() { printf "    ${RED}✗${NC}  %s\n" "$1"; }
info() { printf "       ${DIM}%s${NC}\n" "$1"; }
step() { printf "\n  ${CYN}${BLD}[%d/%d]${NC} %s\n" "$1" "$NSTEPS" "$2"; }

# ─── Konfiguracja ─────────────────────────────────────────────────────────────

if [ -z "${PHP_BIN:-}" ]; then
    if php84 -v >/dev/null 2>&1; then
        PHP_BIN="php84"
    elif php85 -v >/dev/null 2>&1; then
        PHP_BIN="php85"
    elif php83 -v >/dev/null 2>&1; then
        PHP_BIN="php83"
    else
        PHP_BIN="php"
    fi
fi
COMPOSER_BIN="${COMPOSER_BIN:-composer84}"

# Jeśli composer84 nie jest dostępny w PATH, szukamy alternatyw.
if ! command -v "$COMPOSER_BIN" >/dev/null 2>&1; then
    for _c in composer84 composer; do
        if command -v "$_c" >/dev/null 2>&1; then
            COMPOSER_BIN="$_c"
            break
        fi
    done
    # Ostatnia deska ratunku — pełna ścieżka do composer z aktualnym PHP.
    if ! command -v "$COMPOSER_BIN" >/dev/null 2>&1; then
        for _php in \
                /opt/alt/php84/usr/bin/php \
                /opt/alt/php85/usr/bin/php \
                /opt/alt/php83/usr/bin/php; do
            if [ -x "$_php" ] && [ -f /usr/local/bin/composer ]; then
                PHP_BIN="$_php"
                COMPOSER_BIN="/usr/local/bin/composer"
                break
            fi
        done
        unset _php
    fi
    unset _c
fi
NPM_BIN="${NPM_BIN:-/opt/alt/alt-nodejs20/root/usr/bin/npm}"
BRANCH="${BRANCH:-main}"
DB="database/database.sqlite"
BLOG_DB="database/blog.sqlite"

cd "$(dirname "$0")"

# ─── Nagłówek ─────────────────────────────────────────────────────────────────

echo ""
printf "${BLD}┌─────────────────────────────────────────────────────────────┐${NC}\n"
printf "${BLD}│${NC}  %-61s${BLD}│${NC}\n" "Deploy FEER-web"
printf "${BLD}│${NC}  ${DIM}%-61s${NC}${BLD}│${NC}\n" "Gałąź: $BRANCH  |  PHP: $PHP_BIN"
printf "${BLD}└─────────────────────────────────────────────────────────────┘${NC}\n"
echo ""

# ─── Podgląd nadchodzących commitów ───────────────────────────────────────────

info "Pobieranie informacji o zmianach..."
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

# ─── Tryb serwisowy ───────────────────────────────────────────────────────────

step 1 "Tryb serwisowy"
"$PHP_BIN" artisan down --render="errors::503" --retry=30 2>/dev/null || true
ok "Włączony."

_przywroc_serwis() {
    echo ""
    fail "Błąd — wyłączam tryb serwisowy awaryjnie..."
    "$PHP_BIN" artisan up 2>/dev/null || true
}
trap _przywroc_serwis ERR

# ─── Kopia zapasowa baz SQLite ────────────────────────────────────────────────

TS=$(date +%Y%m%d-%H%M%S)
BACKUP=""
BLOG_BACKUP=""

step 2 "Kopia zapasowa baz"
if [ -f "$DB" ]; then
    BACKUP="/tmp/feer-db-$TS.sqlite"
    cp "$DB" "$BACKUP"
    info "Główna  → $BACKUP"
fi
if [ -f "$BLOG_DB" ]; then
    BLOG_BACKUP="/tmp/feer-blog-db-$TS.sqlite"
    cp "$BLOG_DB" "$BLOG_BACKUP"
    info "Blog    → $BLOG_BACKUP"
fi
ok "Gotowe."

# ─── Git pull ─────────────────────────────────────────────────────────────────

step 3 "Pobieranie kodu (git pull)"
git checkout -- "$DB" 2>/dev/null || true
git checkout -- "$BLOG_DB" 2>/dev/null || true
git pull origin "$BRANCH"

if [ -n "$BACKUP" ]; then
    cp "$BACKUP" "$DB"
    info "Przywrócono bazę główną."
fi
if [ -n "$BLOG_BACKUP" ]; then
    cp "$BLOG_BACKUP" "$BLOG_DB"
    info "Przywrócono bazę bloga."
fi
ok "Kod zaktualizowany."

# ─── Composer ─────────────────────────────────────────────────────────────────

COMPOSER_FAILED=0
step 4 "Composer update"
COMPOSER_PATH="$(command -v "$COMPOSER_BIN" 2>/dev/null || echo "$COMPOSER_BIN")"
if "$PHP_BIN" "$COMPOSER_PATH" update \
        --no-interaction \
        --no-dev \
        --optimize-autoloader \
        --quiet; then
    ok "Zależności zaktualizowane."
else
    COMPOSER_FAILED=1
    warn "Composer zakończył błędem — pomijam zależności PHP."
fi

# ─── Assety front-endowe ──────────────────────────────────────────────────────

step 5 "Budowanie assetów (npm)"
export PATH="$(dirname "$NPM_BIN"):$PATH"
if [ -f package-lock.json ]; then
    "$NPM_BIN" ci --silent
else
    "$NPM_BIN" install --silent
fi
"$NPM_BIN" run build
ok "Assety zbudowane."

# ─── Migracje ─────────────────────────────────────────────────────────────────

step 6 "Migracje"
info "Baza główna..."
"$PHP_BIN" artisan migrate --force
info "Baza bloga..."
"$PHP_BIN" artisan migrate --force \
    --database=blog \
    --path=database/migrations/blog
ok "Migracje zastosowane."

# ─── Cache i optymalizacja ────────────────────────────────────────────────────

step 7 "Cache i optymalizacja"
for _cmd in config:clear view:clear route:clear cache:clear; do
    "$PHP_BIN" artisan "$_cmd" --quiet
    info "artisan $_cmd"
done
unset _cmd
"$PHP_BIN" artisan optimize --quiet
info "artisan optimize"
ok "Cache wyczyszczony, kod zoptymalizowany."

# ─── Koniec ───────────────────────────────────────────────────────────────────

trap - ERR
"$PHP_BIN" artisan up

echo ""
if [ "$COMPOSER_FAILED" -eq 1 ]; then
    printf "${YEL}┌─────────────────────────────────────────────────────────────┐${NC}\n"
    printf "${YEL}│${NC}  ${BLD}%-61s${NC}${YEL}│${NC}\n" "Kod pobrany — Composer nie zadziałał."
    printf "${YEL}│${NC}  %-61s${YEL}│${NC}\n" ""
    printf "${YEL}│${NC}  %-61s${YEL}│${NC}\n" "Uruchom ręcznie:"
    printf "${YEL}│${NC}    ${DIM}%-59s${NC}${YEL}│${NC}\n" "php84 /usr/local/bin/composer84 \\"
    printf "${YEL}│${NC}      ${DIM}%-57s${NC}${YEL}│${NC}\n" "update --no-dev --optimize-autoloader"
    printf "${YEL}│${NC}    ${DIM}%-59s${NC}${YEL}│${NC}\n" "php84 artisan migrate --force"
    printf "${YEL}│${NC}    ${DIM}%-59s${NC}${YEL}│${NC}\n" "php84 artisan migrate --force --database=blog \\"
    printf "${YEL}│${NC}      ${DIM}%-57s${NC}${YEL}│${NC}\n" "--path=database/migrations/blog"
    printf "${YEL}│${NC}    ${DIM}%-59s${NC}${YEL}│${NC}\n" "php84 artisan config:clear"
    printf "${YEL}│${NC}    ${DIM}%-59s${NC}${YEL}│${NC}\n" "php84 artisan view:clear"
    printf "${YEL}│${NC}    ${DIM}%-59s${NC}${YEL}│${NC}\n" "php84 artisan optimize"
    printf "${YEL}└─────────────────────────────────────────────────────────────┘${NC}\n"
else
    printf "${GRN}┌─────────────────────────────────────────────────────────────┐${NC}\n"
    printf "${GRN}│${NC}  ${BLD}%-61s${NC}${GRN}│${NC}\n" "Deploy zakończony pomyślnie."
    printf "${GRN}└─────────────────────────────────────────────────────────────┘${NC}\n"
fi
echo ""
