#!/usr/bin/env bash
#
# Deploy FEER-web na serwerze produkcyjnym.
#
# Pobiera zmiany z gita, instaluje zależności, buduje assety, uruchamia migracje
# obu baz (główna i blog), weryfikuje wynik i odświeża cache. Włącza tryb
# serwisowy na czas wdrożenia i automatycznie go wyłącza — także przy błędzie.
#
# Każdy krok musi się udać: błąd Composera, npm, buildu, migracji, brak wpisu
# w manifeście Vite, oczekujące migracje albo błąd kompilacji Blade przerywa
# wdrożenie z kodem ≠ 0 i opisem, co zrobić.
#
# Użycie:
#   ./deploy.sh              wdrożenie
#   ./deploy.sh --dry-run    tylko plan kroków + kontrole (bez pull, bez zmian)
#   ./deploy.sh --help
#
# Zmienne środowiskowe (opcjonalne):
#   PHP_BIN       binarka PHP CLI                (domyślnie: php84)
#   COMPOSER_BIN  binarka Composer               (domyślnie: composer84)
#   COMPOSER_CMD  install | update               (domyślnie: install — dokładnie wersje z composer.lock)
#   NPM_BIN       binarka npm                    (domyślnie: najnowsza z /opt/alt/alt-nodejs24|22|20, potem npm z PATH)
#   BRANCH        gałąź do pobrania              (domyślnie: main)
#
# Przykład lokalnego testu:  PHP_BIN=php COMPOSER_BIN=composer NPM_BIN=npm ./deploy.sh --dry-run
set -euo pipefail

# ─── Argumenty ────────────────────────────────────────────────────────────────

DRY_RUN=0
for _arg in "$@"; do
    case "$_arg" in
        --dry-run|-n) DRY_RUN=1 ;;
        --help|-h)    sed -n '2,26p' "$0" | sed 's/^# \{0,1\}//'; exit 0 ;;
        *)            echo "Nieznana opcja: $_arg (użyj --help)" >&2; exit 2 ;;
    esac
done
unset _arg

# Ustawiane, gdy skrypt uruchamia sam siebie po `git pull`, który przyniósł
# nową wersję deploy.sh — kroki 1–3 zostały już wykonane.
PO_PULL="${DEPLOY_PO_PULL:-0}"

# ─── Kolory ───────────────────────────────────────────────────────────────────

if [ -t 1 ]; then
    GRN=$'\033[0;32m'; YEL=$'\033[0;33m'; RED=$'\033[0;31m'
    CYN=$'\033[0;36m'; BLD=$'\033[1m';    DIM=$'\033[2m'; NC=$'\033[0m'
else
    GRN=''; YEL=''; RED=''; CYN=''; BLD=''; DIM=''; NC=''
fi

NSTEPS=10
ok()   { printf "    ${GRN}✓${NC}  %s\n" "$1"; }
warn() { printf "    ${YEL}⚠${NC}  %s\n" "$1"; }
fail() { printf "    ${RED}✗${NC}  %s\n" "$1"; }
info() { printf "       ${DIM}%s${NC}\n" "$1"; }
step() { printf "\n  ${CYN}${BLD}[%d/%d]${NC} %s\n" "$1" "$NSTEPS" "$2"; CURRENT_STEP="$2"; }

# Lista kontrolna do podsumowania: "status|opis" (status: ok, warn, fail).
SUMMARY=()
mark() { SUMMARY+=("$1|$2"); }

# ─── Konfiguracja ─────────────────────────────────────────────────────────────

# PHP: php84 bywa na hostingu tylko ALIASEM powłoki (działa w terminalu, ale nie
# w skrypcie) — dlatego poza poleceniami z PATH sprawdzamy też ścieżki CloudLinux
# (/opt/alt/phpXX/usr/bin/php). Kolejność: 8.4 → 8.5 → 8.3 → php.
if [ -z "${PHP_BIN:-}" ]; then
    for _p in php84 /opt/alt/php84/usr/bin/php /usr/local/bin/php84 \
              php85 /opt/alt/php85/usr/bin/php /usr/local/bin/php85 \
              php83 /opt/alt/php83/usr/bin/php php; do
        if command -v "$_p" >/dev/null 2>&1 && "$_p" -v >/dev/null 2>&1; then
            PHP_BIN="$_p"
            break
        fi
    done
    unset _p
fi
PHP_BIN="${PHP_BIN:-php}"
# Wymagane PHP >= 8.4 (zależności Composera) — przerwij od razu z jasnym komunikatem.
if ! "$PHP_BIN" -r 'exit(version_compare(PHP_VERSION, "8.4.0", ">=") ? 0 : 1);' 2>/dev/null; then
    printf "\n  ✗  Znalezione PHP (%s, wersja %s) jest starsze niż 8.4.\n" "$PHP_BIN" "$("$PHP_BIN" -r 'echo PHP_VERSION;' 2>/dev/null)"
    printf "     Wskaż PHP 8.4 ręcznie, np.:  PHP_BIN=/opt/alt/php84/usr/bin/php ./deploy.sh\n"
    printf "     (ścieżkę pokaże: type php84  albo  ls /opt/alt/ | grep php)\n\n"
    exit 1
fi
COMPOSER_BIN="${COMPOSER_BIN:-composer84}"
COMPOSER_CMD="${COMPOSER_CMD:-install}"

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
# Najnowszy dostępny Node: część narzędzi (puppeteer, rollup-plugin-visualizer) wymaga Node >= 22.
if [ -z "${NPM_BIN:-}" ]; then
    for _node in alt-nodejs24 alt-nodejs22 alt-nodejs20; do
        if [ -x "/opt/alt/$_node/root/usr/bin/npm" ]; then
            NPM_BIN="/opt/alt/$_node/root/usr/bin/npm"
            break
        fi
    done
    NPM_BIN="${NPM_BIN:-npm}"
fi
# Puppeteer (zależność deweloperska) przy npm ci pobiera całą przeglądarkę Chrome —
# do budowania assetów niepotrzebna, a na hostingu współdzielonym potrafi zawiesić instalację.
export PUPPETEER_SKIP_DOWNLOAD="${PUPPETEER_SKIP_DOWNLOAD:-1}"
BRANCH="${BRANCH:-main}"
DB="database/database.sqlite"
BLOG_DB="database/blog.sqlite"                 # stara lokalizacja (śledzona w gicie)
BLOG_DB_NEW="storage/app/blog/blog.sqlite"     # nowa lokalizacja (docs/blog-db-migration.md)
MANIFEST="public/build/manifest.json"

cd "$(dirname "$0")"

# Composer bywa pharem (#!/usr/bin/env php — uruchamiamy przez $PHP_BIN, żeby
# użyć właściwej wersji PHP) albo skryptem powłoki (composer84 na hostingu —
# uruchamiamy bezpośrednio; podanie skryptu bash do PHP „udaje sukces”).
composer_run() {
    local path
    path="$(command -v "$COMPOSER_BIN" 2>/dev/null || echo "$COMPOSER_BIN")"
    if [ -f "$path" ] && head -n 1 "$path" | grep -q 'php'; then
        "$PHP_BIN" "$path" "$@"
    else
        "$path" "$@"
    fi
}

# Kopia pliku SQLite: sqlite3 .backup (spójna nawet przy WAL), w razie braku — cp.
backup_sqlite() {
    if command -v sqlite3 >/dev/null 2>&1 && sqlite3 "$1" ".backup '$2'" 2>/dev/null; then
        return 0
    fi
    cp "$1" "$2"
}

# Oczekujące migracje wg `artisan migrate:status` (argumenty przekazywane dalej).
pending_migrations() {
    local out
    if ! out=$("$PHP_BIN" artisan migrate:status --no-ansi "$@" 2>&1); then
        printf '%s\n' "$out"
        return 1
    fi
    if printf '%s\n' "$out" | grep -q 'Pending'; then
        printf '%s\n' "$out" | grep 'Pending'
        return 1
    fi
    return 0
}

# ─── Obsługa błędów ───────────────────────────────────────────────────────────

MAINTENANCE_ON="${DEPLOY_MAINTENANCE_ON:-0}"
DEPLOY_DONE=0
CURRENT_STEP="przygotowanie"

print_summary() {
    local entry status text
    echo ""
    printf "  ${BLD}Lista kontrolna${NC}\n"
    for entry in ${SUMMARY[@]+"${SUMMARY[@]}"}; do
        status="${entry%%|*}"; text="${entry#*|}"
        case "$status" in
            ok)   ok "$text" ;;
            warn) warn "$text" ;;
            *)    fail "$text" ;;
        esac
    done
}

# Przerywa wdrożenie: komunikat + kod 1 (trap EXIT zdejmie tryb serwisowy).
die() {
    echo ""
    fail "$1"
    shift
    for _line in "$@"; do info "$_line"; done
    mark fail "$CURRENT_STEP"
    exit 1
}

_na_wyjscie() {
    local code=$?
    [ "$DEPLOY_DONE" -eq 1 ] && return 0
    [ "$DRY_RUN" -eq 1 ] && return 0
    if [ "$code" -ne 0 ]; then
        # Błąd poza die() (set -e) — dopisz przerwany krok do listy.
        case "${SUMMARY[*]-}" in
            *"fail|$CURRENT_STEP"*) ;;
            *) mark fail "$CURRENT_STEP" ;;
        esac
        print_summary
    fi
    if [ "$MAINTENANCE_ON" -eq 1 ]; then
        echo ""
        fail "Wdrożenie przerwane (krok: $CURRENT_STEP) — wyłączam tryb serwisowy."
        "$PHP_BIN" artisan up 2>/dev/null || true
    fi
    if [ "$code" -ne 0 ]; then
        echo ""
        printf "${RED}┌─────────────────────────────────────────────────────────────┐${NC}\n"
        printf "${RED}│${NC}  ${BLD}%-61s${NC}${RED}│${NC}\n" "Deploy NIEUDANY (kod $code)"
        printf "${RED}│${NC}  %-61s${RED}│${NC}\n" "Strona może działać na niepełnej wersji."
        printf "${RED}│${NC}  %-61s${RED}│${NC}\n" "Popraw przyczynę i uruchom ./deploy.sh ponownie."
        printf "${RED}└─────────────────────────────────────────────────────────────┘${NC}\n"
        info "Stan systemu: $PHP_BIN artisan system:sprawdz"
        echo ""
    fi
}
trap _na_wyjscie EXIT
trap 'exit 130' INT TERM

# ─── Tryb próbny (--dry-run) ──────────────────────────────────────────────────

if [ "$DRY_RUN" -eq 1 ]; then
    echo ""
    printf "  ${BLD}Deploy FEER-web — tryb próbny (nic nie jest zmieniane)${NC}\n"
    info "Gałąź: $BRANCH  |  PHP: $PHP_BIN  |  Composer: $COMPOSER_BIN ($COMPOSER_CMD)  |  npm: $NPM_BIN"
    echo ""
    printf "  ${BLD}Planowane kroki${NC}\n"
    info " 1. artisan down (tryb serwisowy)"
    info " 2. kopia baz SQLite do /tmp"
    info " 3. git pull origin $BRANCH (ponowne uruchomienie, jeśli zmienił się deploy.sh)"
    info " 4. composer $COMPOSER_CMD --no-dev --optimize-autoloader"
    info " 5. baza bloga → $BLOG_DB_NEW (artisan blog:przenies-baze, jednorazowo)"
    info " 6. npm ci + npm run build"
    info " 7. weryfikacja $MANIFEST względem input z vite.config.js"
    info " 8. migracje (główna + blog) i kontrola migrate:status (brak Pending)"
    info " 9. optimize:clear → view:cache (kontrola Blade) → optimize"
    info "10. artisan system:sprawdz, artisan up, podsumowanie"
    echo ""
    printf "  ${BLD}Kontrole${NC}\n"
    DRY_FAIL=0
    command -v "$PHP_BIN" >/dev/null 2>&1 && ok "PHP: $PHP_BIN" || { fail "Brak PHP: $PHP_BIN"; DRY_FAIL=1; }
    command -v "$COMPOSER_BIN" >/dev/null 2>&1 || [ -f "$COMPOSER_BIN" ] && ok "Composer: $COMPOSER_BIN" || { fail "Brak Composera: $COMPOSER_BIN"; DRY_FAIL=1; }
    [ -x "$NPM_BIN" ] || command -v "$NPM_BIN" >/dev/null 2>&1 && ok "npm: $NPM_BIN" || { fail "Brak npm: $NPM_BIN"; DRY_FAIL=1; }
    if git fetch origin "$BRANCH" --quiet 2>/dev/null; then
        _n=$(git rev-list --count HEAD..origin/"$BRANCH" 2>/dev/null || echo "?")
        ok "Nowe commity na origin/$BRANCH: $_n"
    else
        warn "Nie udało się pobrać informacji z origin/$BRANCH."
    fi
    if [ -f "$BLOG_DB_NEW" ]; then
        ok "Baza bloga w nowej lokalizacji ($BLOG_DB_NEW)."
    elif [ -f "$BLOG_DB" ]; then
        warn "Baza bloga w starej lokalizacji — krok 5 ją skopiuje do $BLOG_DB_NEW."
    else
        warn "Brak pliku bazy bloga."
    fi
    echo ""
    info "artisan system:sprawdz (stan obecnej wersji kodu):"
    "$PHP_BIN" artisan system:sprawdz || DRY_FAIL=1
    exit "$DRY_FAIL"
fi

TS="${DEPLOY_TS:-$(date +%Y%m%d-%H%M%S)}"

if [ "$PO_PULL" != "1" ]; then

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
MAINTENANCE_ON=1
ok "Włączony."
mark ok "Tryb serwisowy włączony"

# ─── Kopia zapasowa baz SQLite ────────────────────────────────────────────────

BACKUP=""
BLOG_BACKUP=""

step 2 "Kopia zapasowa baz"
if [ -f "$DB" ]; then
    BACKUP="/tmp/feer-db-$TS.sqlite"
    backup_sqlite "$DB" "$BACKUP" || die "Nie udało się skopiować bazy głównej do $BACKUP."
    info "Główna       → $BACKUP"
fi
if [ -f "$BLOG_DB" ]; then
    BLOG_BACKUP="/tmp/feer-blog-db-$TS.sqlite"
    backup_sqlite "$BLOG_DB" "$BLOG_BACKUP" || die "Nie udało się skopiować bazy bloga do $BLOG_BACKUP."
    info "Blog (stara) → $BLOG_BACKUP"
fi
if [ -f "$BLOG_DB_NEW" ]; then
    backup_sqlite "$BLOG_DB_NEW" "/tmp/feer-blog-db-new-$TS.sqlite" \
        || die "Nie udało się skopiować bazy bloga ($BLOG_DB_NEW)."
    info "Blog         → /tmp/feer-blog-db-new-$TS.sqlite"
fi
ok "Gotowe."
mark ok "Kopie baz w /tmp (znacznik $TS)"

# ─── Git pull ─────────────────────────────────────────────────────────────────

step 3 "Pobieranie kodu (git pull)"
DEPLOY_SH_BEFORE=$(git hash-object deploy.sh)
# database/blog.sqlite jest (jeszcze) śledzona w gicie — przywracamy wersję
# z repo na czas pull, a potem kopię z /tmp. Po przeniesieniu bazy do
# storage/app/blog/ ten plik nie jest już używany przez aplikację.
git checkout -- "$DB" 2>/dev/null || true
git checkout -- "$BLOG_DB" 2>/dev/null || true
if ! git pull origin "$BRANCH"; then
    [ -n "$BACKUP" ] && cp "$BACKUP" "$DB"
    [ -n "$BLOG_BACKUP" ] && cp "$BLOG_BACKUP" "$BLOG_DB"
    die "git pull nie powiódł się — bazy przywrócone z kopii." \
        "Sprawdź: git status (konflikty / lokalne zmiany)."
fi

if [ -n "$BACKUP" ]; then
    cp "$BACKUP" "$DB"
    info "Przywrócono bazę główną."
fi
if [ -n "$BLOG_BACKUP" ]; then
    cp "$BLOG_BACKUP" "$BLOG_DB"
    info "Przywrócono bazę bloga (stara lokalizacja)."
fi
ok "Kod zaktualizowany."
mark ok "Kod pobrany ($(git rev-parse --short HEAD))"

# Nowa wersja deploy.sh? Uruchamiamy ją od kroku 4, żeby nowe kroki
# (np. weryfikacje) zadziałały już przy tym wdrożeniu.
if [ "$(git hash-object deploy.sh)" != "$DEPLOY_SH_BEFORE" ]; then
    info "deploy.sh zmienił się w tym pull — uruchamiam nową wersję..."
    trap - EXIT
    exec env DEPLOY_PO_PULL=1 DEPLOY_TS="$TS" DEPLOY_MAINTENANCE_ON=1 \
        PHP_BIN="$PHP_BIN" COMPOSER_BIN="$COMPOSER_BIN" COMPOSER_CMD="$COMPOSER_CMD" \
        NPM_BIN="$NPM_BIN" BRANCH="$BRANCH" bash ./deploy.sh
fi

else
    echo ""
    info "Nowa wersja deploy.sh — kontynuacja od kroku 4 (kroki 1–3 wykonane)."
    mark ok "Kroki 1–3 (tryb serwisowy, kopie baz, git pull) — poprzednia wersja skryptu"
fi

# ─── Composer ─────────────────────────────────────────────────────────────────

step 4 "Composer $COMPOSER_CMD"
if ! composer_run "$COMPOSER_CMD" \
        --no-interaction \
        --no-dev \
        --optimize-autoloader \
        --no-progress; then
    die "Composer zakończył się błędem — wdrożenie przerwane." \
        "Uruchom ręcznie i popraw błąd: $PHP_BIN /usr/local/bin/composer84 $COMPOSER_CMD --no-dev --optimize-autoloader" \
        "(lub: composer84 $COMPOSER_CMD --no-dev --optimize-autoloader)"
fi
[ -f vendor/autoload.php ] || die "Brak vendor/autoload.php po Composerze."
"$PHP_BIN" artisan --version >/dev/null 2>&1 \
    || die "Aplikacja nie startuje po Composerze ($PHP_BIN artisan --version)." "Sprawdź: $PHP_BIN artisan --version"
ok "Zależności zainstalowane."
mark ok "Composer $COMPOSER_CMD"

# ─── Baza bloga — nowa lokalizacja ────────────────────────────────────────────

step 5 "Baza bloga — lokalizacja"
if [ -f "$BLOG_DB_NEW" ]; then
    ok "Już w $BLOG_DB_NEW."
    mark ok "Baza bloga w $BLOG_DB_NEW"
elif [ -f "$BLOG_DB" ]; then
    info "Kopiowanie $BLOG_DB → $BLOG_DB_NEW (z weryfikacją liczby wierszy)..."
    "$PHP_BIN" artisan blog:przenies-baze --force \
        || die "Przeniesienie bazy bloga nie powiodło się — aplikacja dalej używa $BLOG_DB." \
               "Dane są nietknięte. Szczegóły powyżej; ręcznie: $PHP_BIN artisan blog:przenies-baze"
    ok "Baza bloga przeniesiona."
    mark ok "Baza bloga skopiowana do $BLOG_DB_NEW (kopia w storage/app/blog/backups/)"
else
    warn "Brak pliku bazy bloga — migracje bloga utworzą nową."
    mark warn "Brak pliku bazy bloga przed migracjami"
fi

# ─── Assety front-endowe ──────────────────────────────────────────────────────

step 6 "Budowanie assetów (npm)"
export PATH="$(dirname "$NPM_BIN"):$PATH"
if [ -f package-lock.json ]; then
    "$NPM_BIN" ci --silent || die "npm ci nie powiodło się." "Uruchom ręcznie: $NPM_BIN ci"
else
    "$NPM_BIN" install --silent || die "npm install nie powiodło się." "Uruchom ręcznie: $NPM_BIN install"
fi
"$NPM_BIN" run build || die "npm run build nie powiodło się — assety NIE są aktualne." "Uruchom ręcznie: $NPM_BIN run build"
ok "Assety zbudowane."
mark ok "npm ci + npm run build"

# ─── Weryfikacja manifestu Vite ───────────────────────────────────────────────

step 7 "Weryfikacja manifestu Vite"
"$PHP_BIN" artisan system:sprawdz --tylko=manifest \
    || die "Manifest Vite nie zawiera wszystkich wejść z vite.config.js." \
           "Sprawdź wynik npm run build i $MANIFEST."
mark ok "Manifest Vite zawiera wszystkie wejścia z vite.config.js"

# ─── Migracje ─────────────────────────────────────────────────────────────────

step 8 "Migracje"
info "Baza główna..."
"$PHP_BIN" artisan migrate --force \
    || die "Migracje bazy głównej nie powiodły się." "Ręcznie: $PHP_BIN artisan migrate --force"
info "Baza bloga..."
"$PHP_BIN" artisan migrate --force \
    --database=blog \
    --path=database/migrations/blog \
    || die "Migracje bazy bloga nie powiodły się." \
           "Ręcznie: $PHP_BIN artisan migrate --force --database=blog --path=database/migrations/blog"
info "Kontrola migrate:status..."
pending_migrations \
    || die "Baza główna ma oczekujące migracje (Pending) — patrz wyżej."
pending_migrations --database=blog --path=database/migrations/blog \
    || die "Baza bloga ma oczekujące migracje (Pending) — patrz wyżej."
ok "Migracje zastosowane, brak oczekujących."
mark ok "Migracje (główna + blog), brak Pending"

# ─── Cache i optymalizacja ────────────────────────────────────────────────────

step 9 "Cache i optymalizacja"
"$PHP_BIN" artisan optimize:clear --quiet || die "artisan optimize:clear nie powiodło się."
info "artisan optimize:clear"
"$PHP_BIN" artisan cache:clear --quiet || die "artisan cache:clear nie powiodło się."
info "artisan cache:clear"
# view:cache kompiluje wszystkie widoki — błąd w Blade wychodzi teraz, nie u odwiedzających.
"$PHP_BIN" artisan view:cache \
    || die "Kompilacja widoków Blade nie powiodła się (view:cache) — patrz błąd wyżej."
info "artisan view:cache"
"$PHP_BIN" artisan optimize --quiet || die "artisan optimize nie powiodło się."
info "artisan optimize"
ok "Cache odświeżony, widoki skompilowane, kod zoptymalizowany."
mark ok "optimize:clear → view:cache → optimize"

# ─── Kontrola końcowa ─────────────────────────────────────────────────────────

step 10 "Kontrola końcowa (system:sprawdz)"
"$PHP_BIN" artisan system:sprawdz \
    || die "system:sprawdz wykrył problemy — patrz wyżej."
mark ok "system:sprawdz bez błędów"

# ─── Koniec ───────────────────────────────────────────────────────────────────

"$PHP_BIN" artisan up
MAINTENANCE_ON=0
mark ok "Tryb serwisowy wyłączony"
DEPLOY_DONE=1

print_summary
echo ""
printf "${GRN}┌─────────────────────────────────────────────────────────────┐${NC}\n"
printf "${GRN}│${NC}  ${BLD}%-61s${NC}${GRN}│${NC}\n" "Deploy zakończony pomyślnie."
printf "${GRN}└─────────────────────────────────────────────────────────────┘${NC}\n"
echo ""
