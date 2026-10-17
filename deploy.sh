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
# Na ekranie: krok, wynik i czas. Pełne wyjście narzędzi (git, composer, npm,
# artisan) trafia do storage/logs/deploy-<data>.log — przy błędzie pokazujemy
# jego ostatnie linie. Trzymamy 20 ostatnich logów.
#
# Użycie:
#   ./deploy.sh              wdrożenie (pyta o potwierdzenie)
#   ./deploy.sh --tak        bez pytania (-y) — np. ssh h68 './deploy.sh --tak'
#   ./deploy.sh --verbose    pełne wyjście narzędzi na ekranie (-v)
#   ./deploy.sh --dry-run    tylko plan kroków + kontrole (bez pull, bez zmian)
#   ./deploy.sh --help
#
# Zmienne środowiskowe (opcjonalne):
#   PHP_BIN       binarka PHP CLI                (domyślnie: php84)
#   COMPOSER_BIN  binarka Composer               (domyślnie: composer84)
#   COMPOSER_CMD  install | update               (domyślnie: install — dokładnie wersje z composer.lock)
#   NPM_BIN       binarka npm                    (domyślnie: najnowsza z /opt/alt/alt-nodejs24|22|20, potem npm z PATH)
#   BRANCH        gałąź do pobrania              (domyślnie: main)
#   NO_COLOR      wyłącza kolory
#
# Przykład lokalnego testu:  PHP_BIN=php COMPOSER_BIN=composer NPM_BIN=npm ./deploy.sh --dry-run
set -euo pipefail

# ─── Argumenty ────────────────────────────────────────────────────────────────

DRY_RUN=0
ASSUME_YES="${DEPLOY_ASSUME_YES:-0}"
VERBOSE="${DEPLOY_VERBOSE:-0}"
for _arg in "$@"; do
    case "$_arg" in
        --dry-run|-n)  DRY_RUN=1 ;;
        --tak|--yes|-y) ASSUME_YES=1 ;;
        --verbose|-v)  VERBOSE=1 ;;
        --help|-h)     sed -n '2,33p' "$0" | sed 's/^# \{0,1\}//'; exit 0 ;;
        *)             echo "Nieznana opcja: $_arg (użyj --help)" >&2; exit 2 ;;
    esac
done
unset _arg

# Ustawiane, gdy skrypt uruchamia sam siebie po `git pull`, który przyniósł
# nową wersję deploy.sh — kroki 1–3 zostały już wykonane.
PO_PULL="${DEPLOY_PO_PULL:-0}"

# ─── Wygląd ───────────────────────────────────────────────────────────────────

# UTF-8, żeby ${#tekst} i obcinanie liczyły znaki, nie bajty (polskie litery, „─”).
case "${LC_ALL:-${LC_CTYPE:-${LANG:-}}}" in
    *UTF-8*|*utf8*|*UTF8*|*utf-8*) ;;
    *) # Lista do zmiennej: przy pipefail `locale -a | grep -q` kończy się SIGPIPE i warunek zawsze jest fałszywy.
       _locales="$(locale -a 2>/dev/null || true)"
       for _loc in C.UTF-8 C.utf8 en_US.UTF-8 en_US.utf8 pl_PL.UTF-8; do
           if grep -qiE "^${_loc}\$" <<< "$_locales"; then export LC_ALL="$_loc"; break; fi
       done; unset _loc _locales ;;
esac

if [ -t 1 ] && [ -z "${NO_COLOR:-}" ]; then
    GRN=$'\033[0;32m'; YEL=$'\033[0;33m'; RED=$'\033[0;31m'
    CYN=$'\033[0;36m'; BLD=$'\033[1m';    DIM=$'\033[2m'; NC=$'\033[0m'
else
    GRN=''; YEL=''; RED=''; CYN=''; BLD=''; DIM=''; NC=''
fi
COLS=$(tput cols 2>/dev/null || echo "${COLUMNS:-100}")
[ "$COLS" -gt 120 ] 2>/dev/null && COLS=120
[ "$COLS" -lt 60 ] 2>/dev/null && COLS=60

NSTEPS=10
DEPLOY_START="${DEPLOY_START:-$(date +%s)}"
STEP_START=$(date +%s)

# Czas od $1 (epoka) w czytelnej postaci: „<1 s”, „12 s”, „2 min 5 s”.
since() {
    local s=$(( $(date +%s) - $1 ))
    if [ "$s" -lt 1 ]; then echo "<1 s"
    elif [ "$s" -lt 60 ]; then echo "$s s"
    else echo "$((s / 60)) min $((s % 60)) s"; fi
}
# Obcina tekst do $2 znaków (z wielokropkiem).
clip() {
    local t="$1" n="$2"
    if [ "${#t}" -gt "$n" ]; then printf '%s…' "${t:0:$((n - 1))}"; else printf '%s' "$t"; fi
}
# Tekst dopełniony spacjami do $2 znaków (printf %-Ns liczy bajty — źle dla polskich liter).
pad() {
    local t="$1" n="$2"
    printf '%s%*s' "$t" "$(( n > ${#t} ? n - ${#t} : 0 ))" ''
}
# Linia z $1 znaków „─”.
rule() {
    local i out=""
    for ((i = 0; i < $1; i++)); do out+="─"; done
    printf '%s' "$out"
}

step() {
    CURRENT_STEP="$2"; CURRENT_N="$1"; STEP_START=$(date +%s)
    printf "\n  ${CYN}%2d/%d${NC}  ${BLD}%s${NC}\n" "$1" "$NSTEPS" "$2"
}
ok()   { printf "        ${GRN}✓${NC} %s ${DIM}· %s${NC}\n" "$1" "$(since "$STEP_START")"; }
warn() { printf "        ${YEL}⚠${NC} %s\n" "$1"; }
fail() { printf "        ${RED}✗${NC} %s\n" "$1"; }
info() { printf "        ${DIM}%s${NC}\n" "$1"; }

# Lista kontrolna do podsumowania: "status|opis|czas".
SUMMARY=()
mark() { SUMMARY+=("$1|$2|$(since "$STEP_START")"); }

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

TS="${DEPLOY_TS:-$(date +%Y%m%d-%H%M%S)}"

# ─── Log i uruchamianie narzędzi ──────────────────────────────────────────────

mkdir -p storage/logs
LOG="storage/logs/deploy-$TS.log"
touch "$LOG"
RUN_MARK=0

# run polecenie… — wyjście do logu (przy --verbose także na ekran); kod wyjścia polecenia.
run() {
    local rc=0
    RUN_MARK=$(wc -l < "$LOG" | tr -d ' ')
    printf '\n$ %s\n' "$*" >> "$LOG"
    if [ "$VERBOSE" -eq 1 ]; then
        set +e
        "$@" 2>&1 | tee -a "$LOG"
        rc=${PIPESTATUS[0]}
        set -e
    else
        "$@" >> "$LOG" 2>&1 || rc=$?
    fi
    return "$rc"
}
# Wyjście ostatniego run() (do wyciągania skrótów i pokazania przy błędzie).
last_output() { tail -n +"$((RUN_MARK + 1))" "$LOG"; }

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

# Potwierdzenie: --tak pomija pytanie; bez terminala i bez odpowiedzi — przerwij z podpowiedzią.
confirm() {
    [ "$ASSUME_YES" -eq 1 ] && { info "Potwierdzone opcją --tak."; return 0; }
    printf "\n  %s ${DIM}[t/N]${NC} " "$1"
    local answer=""
    if ! read -r answer; then
        echo ""
        echo "  Brak odpowiedzi (uruchomienie bez terminala) — użyj ./deploy.sh --tak"
        exit 1
    fi
    [[ "$answer" =~ ^[tTyY]$ ]] || { echo "  Anulowano."; exit 0; }
}

# ─── Obsługa błędów ───────────────────────────────────────────────────────────

MAINTENANCE_ON="${DEPLOY_MAINTENANCE_ON:-0}"
DEPLOY_DONE=0
CURRENT_STEP="przygotowanie"
CURRENT_N=0

print_summary() {
    local entry status rest text took icon
    echo ""
    printf "  ${BLD}Podsumowanie${NC}\n"
    for entry in ${SUMMARY[@]+"${SUMMARY[@]}"}; do
        status="${entry%%|*}"; rest="${entry#*|}"; text="${rest%|*}"; took="${rest##*|}"
        case "$status" in
            ok)   icon="${GRN}✓${NC}" ;;
            warn) icon="${YEL}⚠${NC}" ;;
            *)    icon="${RED}✗${NC}" ;;
        esac
        printf "    %s %s ${DIM}%8s${NC}\n" "$icon" "$(pad "$(clip "$text" $((COLS - 18)))" $((COLS - 18)))" "$took"
    done
}

# Przerywa wdrożenie: komunikat, ostatnie linie wyjścia narzędzia + kod 1 (trap EXIT zdejmie tryb serwisowy).
die() {
    echo ""
    fail "$1"
    shift
    for _line in "$@"; do info "$_line"; done
    if [ "$RUN_MARK" -gt 0 ] || [ -s "$LOG" ]; then
        local tail_lines
        tail_lines="$(last_output | grep -v '^\s*$' | tail -n 20)"
        if [ -n "$tail_lines" ] && [ "$VERBOSE" -ne 1 ]; then
            echo ""
            info "Ostatnie linie wyjścia:"
            printf '%s\n' "$tail_lines" | sed "s/^/          ${DIM}│${NC} /"
        fi
    fi
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
        "$PHP_BIN" artisan up >> "$LOG" 2>&1 || true
        echo ""
        warn "Tryb serwisowy wyłączony — strona działa, ale może mieć niepełną wersję."
    fi
    if [ "$code" -ne 0 ]; then
        echo ""
        printf "  ${RED}${BLD}✗ Wdrożenie NIEUDANE${NC} — krok %s/%s „%s” (kod %s, %s)\n" \
            "$CURRENT_N" "$NSTEPS" "$CURRENT_STEP" "$code" "$(since "$DEPLOY_START")"
        info "Pełny log:    $LOG"
        info "Stan systemu: $PHP_BIN artisan system:sprawdz"
        info "Po poprawce uruchom ./deploy.sh ponownie."
        echo ""
    fi
}
trap _na_wyjscie EXIT
trap 'exit 130' INT TERM

# ─── Nagłówek ─────────────────────────────────────────────────────────────────

header() {
    local php_ver node_ver line
    php_ver="$("$PHP_BIN" -r 'echo PHP_VERSION;' 2>/dev/null || echo '?')"
    node_ver="$("$(dirname "$NPM_BIN")/node" -v 2>/dev/null || node -v 2>/dev/null || echo '?')"
    line="$(rule $((COLS - 4)))"
    echo ""
    printf "  ${BLD}FEER-web · %s${NC}\n" "$1"
    printf "  ${DIM}%s${NC}\n" "$line"
    printf "  ${DIM}%s${NC} %s\n" "$(pad Serwer 9)" "$(hostname 2>/dev/null || echo '?')"
    printf "  ${DIM}%s${NC} %s\n" "$(pad Katalog 9)" "$(pwd)"
    printf "  ${DIM}%s${NC} %s ${DIM}(%s)${NC}\n" "$(pad Gałąź 9)" "$BRANCH" "$(git rev-parse --short HEAD 2>/dev/null || echo '?')"
    printf "  ${DIM}%s${NC} %s ${DIM}%s${NC}\n" "$(pad PHP 9)" "$php_ver" "$PHP_BIN"
    printf "  ${DIM}%s${NC} %s ${DIM}%s${NC}\n" "$(pad Node 9)" "$node_ver" "$NPM_BIN"
    [ "$DRY_RUN" -eq 1 ] || printf "  ${DIM}%s${NC} %s\n" "$(pad Log 9)" "$LOG"
}

# ─── Tryb próbny (--dry-run) ──────────────────────────────────────────────────

if [ "$DRY_RUN" -eq 1 ]; then
    rm -f "$LOG"
    header "tryb próbny (nic nie jest zmieniane)"
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
    STEP_START=$(date +%s)
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

# Stare logi wdrożeń: zostaw 20 ostatnich.
ls -1t storage/logs/deploy-*.log 2>/dev/null | tail -n +21 | xargs rm -f 2>/dev/null || true

if [ "$PO_PULL" != "1" ]; then

header "wdrożenie"

# ─── Podgląd nadchodzących commitów ───────────────────────────────────────────

echo ""
info "Pobieranie informacji o zmianach…"
run git fetch origin "$BRANCH" || die "git fetch nie powiódł się." "Sprawdź połączenie z GitHubem: git fetch origin $BRANCH"

COUNT=$(git rev-list --count HEAD..origin/"$BRANCH" 2>/dev/null || echo 0)
if [ "$COUNT" -eq 0 ]; then
    printf "\n  ${BLD}Brak nowych commitów${NC} ${DIM}— kod na serwerze jest już aktualny (np. po automatycznym pobraniu po pushu).${NC}\n"
    info "Deploy i tak odświeży zależności, assety, migracje i cache."
    confirm "Kontynuować?"
else
    printf "\n  ${BLD}Commity do wdrożenia (%s)${NC}\n" "$COUNT"
    _w=$((COLS - 32))
    git log --format='%h%x09%s%x09%an%x09%cr' -n 15 HEAD..origin/"$BRANCH" | while IFS=$'\t' read -r _h _s _a _r; do
        printf "    ${CYN}%s${NC}  %s ${DIM}%s${NC}\n" "$_h" "$(pad "$(clip "$_s" "$_w")" "$_w")" "$(clip "$_r" 14)"
    done
    [ "$COUNT" -gt 15 ] && info "… i $((COUNT - 15)) starszych"
    confirm "Wdrożyć $( [ "$COUNT" -eq 1 ] && echo 'ten commit' || echo "te $COUNT commity" )?"
fi

# ─── Tryb serwisowy ───────────────────────────────────────────────────────────

step 1 "Tryb serwisowy"
run "$PHP_BIN" artisan down --render="errors::503" --retry=30 || true
MAINTENANCE_ON=1
ok "Włączony (odwiedzający widzą stronę 503 z ponowieniem za 30 s)."
mark ok "Tryb serwisowy włączony"

# ─── Kopia zapasowa baz SQLite ────────────────────────────────────────────────

BACKUP=""
BLOG_BACKUP=""

step 2 "Kopia zapasowa baz"
if [ -f "$DB" ]; then
    BACKUP="/tmp/feer-db-$TS.sqlite"
    backup_sqlite "$DB" "$BACKUP" || die "Nie udało się skopiować bazy głównej do $BACKUP."
    info "Główna       → $BACKUP ($(du -h "$BACKUP" | cut -f1))"
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
ok "Kopie gotowe."
mark ok "Kopie baz w /tmp (znacznik $TS)"

# ─── Git pull ─────────────────────────────────────────────────────────────────

step 3 "Pobieranie kodu (git pull)"
DEPLOY_SH_BEFORE=$(git hash-object deploy.sh)
HEAD_BEFORE=$(git rev-parse --short HEAD)
# database/blog.sqlite jest (jeszcze) śledzona w gicie — przywracamy wersję
# z repo na czas pull, a potem kopię z /tmp. Po przeniesieniu bazy do
# storage/app/blog/ ten plik nie jest już używany przez aplikację.
# Usuniętej poprzedniej bazy SQLite (serwis na MySQL, „Usuń poprzednią bazę”) nie odtwarzamy z gita.
[ -f "$DB" ] && git checkout -- "$DB" 2>/dev/null || true
[ -f "$BLOG_DB" ] && git checkout -- "$BLOG_DB" 2>/dev/null || true
if ! run git pull origin "$BRANCH"; then
    [ -n "$BACKUP" ] && cp "$BACKUP" "$DB"
    [ -n "$BLOG_BACKUP" ] && cp "$BLOG_BACKUP" "$BLOG_DB"
    die "git pull nie powiódł się — bazy przywrócone z kopii." \
        "Sprawdź: git status (konflikty / lokalne zmiany)."
fi
_stat="$(last_output | grep -E 'files? changed' | tail -n 1 | sed 's/^ *//')"
[ -n "$_stat" ] && info "$HEAD_BEFORE → $(git rev-parse --short HEAD): $_stat"

if [ -n "$BACKUP" ]; then
    cp "$BACKUP" "$DB"
    info "Przywrócono bazę główną z kopii."
fi
if [ -n "$BLOG_BACKUP" ]; then
    cp "$BLOG_BACKUP" "$BLOG_DB"
    info "Przywrócono bazę bloga (stara lokalizacja)."
fi
ok "Kod zaktualizowany ($(git rev-parse --short HEAD))."
mark ok "Kod pobrany ($(git rev-parse --short HEAD))"

# Nowa wersja deploy.sh? Uruchamiamy ją od kroku 4, żeby nowe kroki
# (np. weryfikacje) zadziałały już przy tym wdrożeniu.
if [ "$(git hash-object deploy.sh)" != "$DEPLOY_SH_BEFORE" ]; then
    info "deploy.sh zmienił się w tym pull — uruchamiam nową wersję…"
    trap - EXIT
    exec env DEPLOY_PO_PULL=1 DEPLOY_TS="$TS" DEPLOY_MAINTENANCE_ON=1 DEPLOY_START="$DEPLOY_START" \
        DEPLOY_ASSUME_YES="$ASSUME_YES" DEPLOY_VERBOSE="$VERBOSE" \
        PHP_BIN="$PHP_BIN" COMPOSER_BIN="$COMPOSER_BIN" COMPOSER_CMD="$COMPOSER_CMD" \
        NPM_BIN="$NPM_BIN" BRANCH="$BRANCH" bash ./deploy.sh
fi

else
    header "wdrożenie (ciąg dalszy)"
    echo ""
    info "Nowa wersja deploy.sh — kontynuacja od kroku 4 (kroki 1–3 wykonane)."
    mark ok "Kroki 1–3 (tryb serwisowy, kopie baz, git pull) — poprzednia wersja skryptu"
fi

# ─── Composer ─────────────────────────────────────────────────────────────────

step 4 "Composer $COMPOSER_CMD"
if ! run composer_run "$COMPOSER_CMD" \
        --no-interaction \
        --no-dev \
        --optimize-autoloader \
        --no-progress; then
    die "Composer zakończył się błędem — wdrożenie przerwane." \
        "Uruchom ręcznie i popraw błąd: $PHP_BIN /usr/local/bin/composer84 $COMPOSER_CMD --no-dev --optimize-autoloader" \
        "(lub: composer84 $COMPOSER_CMD --no-dev --optimize-autoloader)"
fi
[ -f vendor/autoload.php ] || die "Brak vendor/autoload.php po Composerze."
run "$PHP_BIN" artisan --version \
    || die "Aplikacja nie startuje po Composerze ($PHP_BIN artisan --version)." "Sprawdź: $PHP_BIN artisan --version"
_cs="$(grep -E 'Nothing to install|Installing|Updating|Removing|Package operations' "$LOG" | tail -n 1 | sed 's/^ *//; s/^- //')"
ok "Zależności zainstalowane${_cs:+ — $_cs}."
mark ok "Composer $COMPOSER_CMD"

# ─── Baza bloga — nowa lokalizacja ────────────────────────────────────────────

step 5 "Baza bloga — lokalizacja"
if [ -f "$BLOG_DB_NEW" ]; then
    ok "Już w $BLOG_DB_NEW."
    mark ok "Baza bloga w $BLOG_DB_NEW"
elif [ -f "$BLOG_DB" ]; then
    info "Kopiowanie $BLOG_DB → $BLOG_DB_NEW (z weryfikacją liczby wierszy)…"
    run "$PHP_BIN" artisan blog:przenies-baze --force \
        || die "Przeniesienie bazy bloga nie powiodło się — aplikacja dalej używa $BLOG_DB." \
               "Dane są nietknięte. Ręcznie: $PHP_BIN artisan blog:przenies-baze"
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
    run "$NPM_BIN" ci --no-audit --no-fund || die "npm ci nie powiodło się." "Uruchom ręcznie: $NPM_BIN ci"
else
    run "$NPM_BIN" install --no-audit --no-fund || die "npm install nie powiodło się." "Uruchom ręcznie: $NPM_BIN install"
fi
info "Zależności npm zainstalowane."
run "$NPM_BIN" run build || die "npm run build nie powiodło się — assety NIE są aktualne." "Uruchom ręcznie: $NPM_BIN run build"
_built="$(last_output | grep -oE 'built in [0-9.]+ ?m?s' | tail -n 1)"
ok "Assety zbudowane${_built:+ (vite: $_built)}."
mark ok "npm ci + npm run build"

# ─── Weryfikacja manifestu Vite ───────────────────────────────────────────────

step 7 "Weryfikacja manifestu Vite"
run "$PHP_BIN" artisan system:sprawdz --tylko=manifest --no-ansi \
    || die "Manifest Vite nie zawiera wszystkich wejść z vite.config.js." \
           "Sprawdź wynik npm run build i $MANIFEST."
ok "Manifest zawiera wszystkie wejścia z vite.config.js."
mark ok "Manifest Vite zawiera wszystkie wejścia z vite.config.js"

# ─── Migracje ─────────────────────────────────────────────────────────────────

# Migracje wykonane w ostatnim run() („nazwa …… 12ms DONE”) — liczba, a pod spodem nazwy.
migrations_done() {
    local names n
    names="$(last_output | grep -E 'DONE *$' | sed -E 's/^ *//; s/ \.+.*$//' || true)"
    if [ -z "$names" ]; then
        info "$1: brak nowych migracji"
        return
    fi
    n="$(printf '%s\n' "$names" | wc -l | tr -d ' ')"
    info "$1: zastosowano $n"
    while IFS= read -r _m; do info "  · $_m"; done <<< "$names"
}

step 8 "Migracje"
run "$PHP_BIN" artisan migrate --force --no-ansi \
    || die "Migracje bazy głównej nie powiodły się." "Ręcznie: $PHP_BIN artisan migrate --force"
migrations_done "Baza główna"
run "$PHP_BIN" artisan migrate --force --no-ansi \
    --database=blog \
    --path=database/migrations/blog \
    || die "Migracje bazy bloga nie powiodły się." \
           "Ręcznie: $PHP_BIN artisan migrate --force --database=blog --path=database/migrations/blog"
migrations_done "Baza bloga"
_pending="$(pending_migrations)" \
    || die "Baza główna ma oczekujące migracje (Pending)." "$_pending"
_pending="$(pending_migrations --database=blog --path=database/migrations/blog)" \
    || die "Baza bloga ma oczekujące migracje (Pending)." "$_pending"
ok "Migracje zastosowane, brak oczekujących."
mark ok "Migracje (główna + blog), brak Pending"

# ─── Cache i optymalizacja ────────────────────────────────────────────────────

step 9 "Cache i optymalizacja"
run "$PHP_BIN" artisan optimize:clear --quiet || die "artisan optimize:clear nie powiodło się."
run "$PHP_BIN" artisan cache:clear --quiet || die "artisan cache:clear nie powiodło się."
# view:cache kompiluje wszystkie widoki — błąd w Blade wychodzi teraz, nie u odwiedzających.
run "$PHP_BIN" artisan view:cache --no-ansi \
    || die "Kompilacja widoków Blade nie powiodła się (view:cache)."
run "$PHP_BIN" artisan optimize --quiet || die "artisan optimize nie powiodło się."
ok "optimize:clear → cache:clear → view:cache → optimize."
mark ok "optimize:clear → view:cache → optimize"

# ─── Kontrola końcowa ─────────────────────────────────────────────────────────

step 10 "Kontrola końcowa (system:sprawdz)"
SPRAWDZ_RC=0
run "$PHP_BIN" artisan system:sprawdz --no-ansi || SPRAWDZ_RC=$?
# Na ekran tylko to, co wymaga uwagi; komplet w logu.
# Wiersze kontroli kończą się etykietą OK / UWAGA / PROBLEM (długie — bez kropek).
_checks="$(last_output | grep -E ' (OK|UWAGA|PROBLEM) *$' || true)"
_okc="$(printf '%s\n' "$_checks" | grep -cE ' OK *$' || true)"
_bad="$(printf '%s\n' "$_checks" | grep -E ' (UWAGA|PROBLEM) *$' || true)"
if [ -n "$_bad" ]; then
    while IFS= read -r _l; do
        _l="$(printf '%s' "$_l" | sed -E 's/ \.{3,} / — /; s/ +(UWAGA|PROBLEM) *$/ — \1/; s/^ *//')"
        warn "$(clip "$_l" $((COLS - 12)))"
    done <<< "$_bad"
fi
[ "$SPRAWDZ_RC" -eq 0 ] || die "system:sprawdz wykrył problemy."
ok "Kontroli bez zastrzeżeń: $_okc$( [ -n "$_bad" ] && echo ", ostrzeżenia: $(printf '%s\n' "$_bad" | wc -l | tr -d ' ')")."
if [ -n "$_bad" ]; then
    mark warn "system:sprawdz bez błędów, z ostrzeżeniami"
else
    mark ok "system:sprawdz bez błędów"
fi

# ─── Koniec ───────────────────────────────────────────────────────────────────

STEP_START=$(date +%s)
run "$PHP_BIN" artisan up || true
MAINTENANCE_ON=0
mark ok "Tryb serwisowy wyłączony"
DEPLOY_DONE=1

print_summary
echo ""
printf "  ${GRN}${BLD}✓ Wdrożenie zakończone${NC} — %s w %s\n" "$(git rev-parse --short HEAD)" "$(since "$DEPLOY_START")"
info "Pełny log: $LOG"
echo ""
