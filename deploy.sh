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
#   ./deploy.sh --demo       podgląd wyglądu kreatora: przykładowy przebieg, nic nie jest zmieniane
#   ./deploy.sh --demo-blad  to samo z błędem w kroku 6 (jak wygląda przerwane wdrożenie)
#   ./deploy.sh --help
#
# Zmienne środowiskowe (opcjonalne):
#   PHP_BIN       binarka PHP CLI                (domyślnie: php84)
#   COMPOSER_BIN  binarka Composer               (domyślnie: composer84)
#   COMPOSER_CMD  install | update               (domyślnie: install — dokładnie wersje z composer.lock)
#   NPM_BIN       binarka npm                    (domyślnie: najnowsza z /opt/alt/alt-nodejs24|22|20, potem npm z PATH)
#   BRANCH        gałąź do pobrania              (domyślnie: main)
#   NO_COLOR      wyłącza kolory
#   FORCE_COLOR   wymusza kolory także bez terminala (np. do zapisu podglądu --demo)
#
# Przykład lokalnego testu:  PHP_BIN=php COMPOSER_BIN=composer NPM_BIN=npm ./deploy.sh --dry-run
set -euo pipefail

# ─── Argumenty ────────────────────────────────────────────────────────────────

DRY_RUN=0
DEMO=0
ASSUME_YES="${DEPLOY_ASSUME_YES:-0}"
VERBOSE="${DEPLOY_VERBOSE:-0}"
for _arg in "$@"; do
    case "$_arg" in
        --dry-run|-n)  DRY_RUN=1 ;;
        --tak|--yes|-y) ASSUME_YES=1 ;;
        --verbose|-v)  VERBOSE=1 ;;
        --demo)        DEMO=1; ASSUME_YES=1 ;;
        --demo-blad)   DEMO=2; ASSUME_YES=1 ;;
        --help|-h)     awk 'NR > 1 { if (/^#/) { sub(/^# ?/, ""); print } else exit }' "$0"; exit 0 ;;
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

case "${LC_ALL:-${LC_CTYPE:-${LANG:-}}}" in
    *UTF-8*|*utf8*|*UTF8*|*utf-8*) UTF=1 ;;
    *) UTF=0 ;;
esac

# Kolory tylko na terminalu (albo z FORCE_COLOR=1) i bez NO_COLOR.
# BRAND = niebieski marki (#1E6DFF ≈ 256-kolorowy 33), AMB = bursztyn marki (#EA8F00 ≈ 208).
if { [ -t 1 ] || [ -n "${FORCE_COLOR:-}" ]; } && [ -z "${NO_COLOR:-}" ] && [ "${TERM:-}" != "dumb" ]; then
    GRN=$'\033[0;32m'; YEL=$'\033[0;33m'; RED=$'\033[0;31m'
    CYN=$'\033[0;36m'; BLD=$'\033[1m';    DIM=$'\033[2m'; NC=$'\033[0m'
    BRAND=$'\033[38;5;33m'; AMB=$'\033[38;5;208m'
else
    GRN=''; YEL=''; RED=''; CYN=''; BLD=''; DIM=''; NC=''; BRAND=''; AMB=''
fi

# Animacja (spinner przy długich krokach): tylko na prawdziwym terminalu i bez --verbose.
ANIM=0
if [ -t 1 ] && [ "${TERM:-dumb}" != "dumb" ] && [ "$VERBOSE" -ne 1 ]; then ANIM=1; fi

# Znaki: ramki i ikony w UTF-8, w innym środowisku zwykłe ASCII.
if [ "$UTF" -eq 1 ]; then
    G_H='─'; G_V='│'; G_TL='╭'; G_TR='╮'; G_BL='╰'; G_BR='╯'; G_ML='├'; G_MR='┤'
    G_OK='✓'; G_WARN='⚠'; G_FAIL='✗'; G_ON='●'; G_OFF='○'; G_BAR='▌'; G_ASK='❯'; G_DOT='·'
    G_FULL='█'; G_EMPTY='░'; G_ELL='…'; G_ARROW='›'
    SPIN=('⠋' '⠙' '⠹' '⠸' '⠼' '⠴' '⠦' '⠧' '⠇' '⠏')
else
    G_H='-'; G_V='|'; G_TL='+'; G_TR='+'; G_BL='+'; G_BR='+'; G_ML='+'; G_MR='+'
    G_OK='+'; G_WARN='!'; G_FAIL='x'; G_ON='#'; G_OFF='.'; G_BAR='|'; G_ASK='>'; G_DOT='-'
    G_FULL='#'; G_EMPTY='.'; G_ELL='...'; G_ARROW='>'
    SPIN=('|' '/' '-' '\')
fi

COLS=$(tput cols 2>/dev/null || echo "${COLUMNS:-100}")
[ "$COLS" -gt 120 ] 2>/dev/null && COLS=120
[ "$COLS" -lt 60 ] 2>/dev/null && COLS=60
# Szerokość ramki (z obramowaniem), tekst w środku i prawy brzeg treści (po dwóch spacjach wcięcia).
BOXW=$((COLS - 4))
if [ "$BOXW" -gt 96 ]; then BOXW=96; fi
INNER=$((BOXW - 6))
EDGE=$((BOXW + 2))

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
    local t="$1" n="$2" k
    if [ "$n" -lt 1 ]; then return 0; fi
    if [ "${#t}" -gt "$n" ]; then
        k=$((n - ${#G_ELL})); if [ "$k" -lt 0 ]; then k=0; fi
        printf '%s%s' "${t:0:$k}" "$G_ELL"
    else
        printf '%s' "$t"
    fi
}
# Tekst dopełniony spacjami do $2 znaków (printf %-Ns liczy bajty — źle dla polskich liter).
pad() {
    local t="$1" n="$2"
    printf '%s%*s' "$t" "$(( n > ${#t} ? n - ${#t} : 0 ))" ''
}
# $2 kopii znaku $1.
rep() {
    local i out=""
    for ((i = 0; i < $2; i++)); do out+="$1"; done
    printf '%s' "$out"
}
# Linia z $1 znaków poziomej kreski.
rule() { rep "$G_H" "$1"; }

# „1 commit”, „3 commity”, „5 commitów”.
commits_pl() {
    local n="$1" m10=$(( $1 % 10 )) m100=$(( $1 % 100 ))
    if [ "$n" -eq 1 ]; then echo "1 commit"
    elif [ "$m10" -ge 2 ] && [ "$m10" -le 4 ] && { [ "$m100" -lt 12 ] || [ "$m100" -gt 14 ]; }; then echo "$n commity"
    else echo "$n commitów"; fi
}

# ─── Ramki ────────────────────────────────────────────────────────────────────
# $1 = kolor ramki. box_line: $2 = tekst (może mieć kolory), $3 = jego widoczna długość.
box_top()    { printf '  %s%s%s%s%s\n' "$1" "$G_TL" "$(rule $((BOXW - 2)))" "$G_TR" "$NC"; }
box_sep()    { printf '  %s%s%s%s%s\n' "$1" "$G_ML" "$(rule $((BOXW - 2)))" "$G_MR" "$NC"; }
box_bottom() { printf '  %s%s%s%s%s\n' "$1" "$G_BL" "$(rule $((BOXW - 2)))" "$G_BR" "$NC"; }
box_line() {
    local gap=$(( INNER - $3 ))
    if [ "$gap" -lt 0 ]; then gap=0; fi
    printf '  %s%s%s  %s%*s  %s%s%s\n' "$1" "$G_V" "$NC" "$2" "$gap" '' "$1" "$G_V" "$NC"
}
# Tytuł ramki: lewy pogrubiony ($2), prawy przyciemniony ($3), kolor tytułu $4 (domyślnie marki).
box_title() {
    local color="${4:-$BRAND}" left right gap
    left="$(clip "$2" "$INNER")"
    right="$3"
    gap=$(( INNER - ${#left} - ${#right} ))
    if [ "$gap" -lt 1 ]; then right=""; gap=$(( INNER - ${#left} )); fi
    box_line "$1" "${BLD}${color}${left}${NC}$(printf '%*s' "$gap" '')${DIM}${right}${NC}" "$INNER"
}
# Wiersz „etykieta  wartość [dopisek]”: etykieta przyciemniona, wartość obcięta, dopisek tylko gdy się mieści.
box_kv() {
    local val ext="${4:-}" vw=$((INNER - 10)) vis colored
    val="$(clip "$3" "$vw")"
    vis=$((10 + ${#val}))
    colored="${DIM}$(pad "$2" 9)${NC} ${val}"
    if [ -n "$ext" ] && [ $((vis + 1 + ${#ext})) -le "$INNER" ]; then
        colored+=" ${DIM}${ext}${NC}"
        vis=$((vis + 1 + ${#ext}))
    fi
    box_line "$1" "$colored" "$vis"
}

# ─── Kroki i komunikaty ───────────────────────────────────────────────────────
# Nagłówek kroku: pionowy znacznik marki, „n/N”, tytuł i pasek postępu z kropek (zielone = gotowe, niebieska = bieżący).
step() {
    local n="$1" i dots="" lead title room gap
    CURRENT_STEP="$2"; CURRENT_N="$1"; STEP_START=$(date +%s)
    for ((i = 1; i <= NSTEPS; i++)); do
        if   [ "$i" -lt "$n" ]; then dots+="${GRN}${G_ON}${NC}"
        elif [ "$i" -eq "$n" ]; then dots+="${BRAND}${G_ON}${NC}"
        else dots+="${DIM}${G_OFF}${NC}"; fi
    done
    lead="$n/$NSTEPS"
    room=$((EDGE - 6 - ${#lead} - NSTEPS - 2))
    title="$(clip "$2" "$room")"
    gap=$((EDGE - 6 - ${#lead} - ${#title} - NSTEPS))
    printf "\n  ${BRAND}%s${NC} ${BLD}%s${NC}  ${BLD}%s${NC}%*s%s\n" "$G_BAR" "$lead" "$title" "$gap" '' "$dots"
}
ok()   { printf "     ${GRN}%s${NC} %s ${DIM}%s %s${NC}\n" "$G_OK" "$1" "$G_DOT" "$(since "$STEP_START")"; }
warn() { printf "     ${AMB}%s${NC} %s\n" "$G_WARN" "$1"; }
fail() { printf "     ${RED}%s${NC} %s\n" "$G_FAIL" "$1"; }
info() { printf "     ${DIM}%s${NC}\n" "$1"; }

# Lista kontrolna do podsumowania: "status|opis|czas|sekundy".
SUMMARY=()
mark() { SUMMARY+=("$1|$2|$(since "$STEP_START")|$(( $(date +%s) - STEP_START ))"); }

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

if [ "$DEMO" -ge 1 ]; then
    # Podgląd wyglądu: log w pliku tymczasowym (sprząta _sprzatanie), storage/logs i pulpit „Kondycja serwisu” bez zmian.
    LOG="$(mktemp "${TMPDIR:-/tmp}/feer-deploy-demo.XXXXXX")"
else
    mkdir -p storage/logs
    LOG="storage/logs/deploy-$TS.log"
fi
touch "$LOG"
# Znaczniki dla pulpitu „Kondycja serwisu” (HealthDashboard::parseDeployLog): start i wynik w czasie epoki.
grep -q '^# start:' "$LOG" 2>/dev/null || printf '# start: %s\n' "$DEPLOY_START" >> "$LOG"
RUN_MARK=0

# run polecenie… — wyjście do logu (przy --verbose także na ekran); kod wyjścia polecenia.
# Na terminalu polecenie działa w tle, a skrypt rysuje spinner z czasem bieżącego kroku (RUN_PID zabija _na_wyjscie).
RUN_PID=""
run() {
    local rc=0 i=0 t0 frame
    RUN_MARK=$(wc -l < "$LOG" | tr -d ' ')
    printf '\n$ %s\n' "$*" >> "$LOG"
    if [ "$VERBOSE" -eq 1 ]; then
        set +e
        "$@" 2>&1 | tee -a "$LOG"
        rc=${PIPESTATUS[0]}
        set -e
    elif [ "$ANIM" -eq 1 ]; then
        "$@" >> "$LOG" 2>&1 &
        RUN_PID=$!
        t0=$(date +%s)
        while kill -0 "$RUN_PID" 2>/dev/null; do
            # Spinner dopiero po ok. pół sekundy, żeby szybkie polecenia nie migały.
            if [ "$i" -ge 4 ]; then
                frame="${SPIN[$((i % ${#SPIN[@]}))]}"
                printf '\r\033[K     %s%s%s %s%s %s %s%s' "$BRAND" "$frame" "$NC" "$DIM" "$(clip "$CURRENT_STEP" $((EDGE - 24)))" "$G_DOT" "$(since "$t0")" "$NC"
            fi
            i=$((i + 1))
            sleep 0.12 2>/dev/null || sleep 1
        done
        wait "$RUN_PID" || rc=$?
        RUN_PID=""
        printf '\r\033[K'
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
    printf "\n  ${BRAND}%s${NC} ${BLD}%s${NC} ${DIM}[t/N]${NC} " "$G_ASK" "$1"
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

# Podsumowanie: ikona, opis, czas i miniaturowy pasek udziału kroku w całym czasie.
print_summary() {
    local entry status text took secs total=0 tw n bar icon i
    for entry in ${SUMMARY[@]+"${SUMMARY[@]}"}; do
        IFS='|' read -r status text took secs <<< "$entry"
        total=$((total + secs))
    done
    tw=$((EDGE - 26))
    echo ""
    printf "  ${BLD}Podsumowanie${NC}\n"
    printf "  ${DIM}%s${NC}\n" "$(rule "$BOXW")"
    for entry in ${SUMMARY[@]+"${SUMMARY[@]}"}; do
        IFS='|' read -r status text took secs <<< "$entry"
        case "$status" in
            ok)   icon="${GRN}${G_OK}${NC}" ;;
            warn) icon="${AMB}${G_WARN}${NC}" ;;
            *)    icon="${RED}${G_FAIL}${NC}" ;;
        esac
        n=0
        if [ "$total" -gt 0 ] && [ "$secs" -gt 0 ]; then n=$(( (secs * 10 + total - 1) / total )); fi
        bar="${BRAND}$(rep "$G_FULL" "$n")${NC}${DIM}$(rep "$G_EMPTY" $((10 - n)))${NC}"
        printf "  %s %s ${DIM}%9s${NC}  %s\n" "$icon" "$(pad "$(clip "$text" "$tw")" "$tw")" "$took" "$bar"
    done
    return 0
}

# Ramka z wynikiem: sukces (zielona) albo porażka (czerwona).
print_success() {
    local commit txt
    commit="$(git rev-parse --short HEAD 2>/dev/null || echo '?')"
    txt="commit $commit"
    echo ""
    box_top "$GRN"
    box_title "$GRN" "$G_OK Wdrożenie zakończone" "$(since "$DEPLOY_START")" "$GRN"
    box_line "$GRN" "$txt" "${#txt}"
    box_bottom "$GRN"
    info "Pełny log: $LOG"
    echo ""
}
print_failure() {
    local txt
    txt="krok $CURRENT_N/$NSTEPS „$(clip "$CURRENT_STEP" $((INNER - 24)))” (kod $1)"
    echo ""
    box_top "$RED"
    box_title "$RED" "$G_FAIL Wdrożenie NIEUDANE" "$(since "$DEPLOY_START")" "$RED"
    box_line "$RED" "$txt" "${#txt}"
    box_bottom "$RED"
    info "Pełny log:    $LOG"
    info "Stan systemu: ${PHP_LABEL:-$PHP_BIN} artisan system:sprawdz"
    info "Po poprawce uruchom ./deploy.sh ponownie."
    echo ""
}

# Przerywa wdrożenie: komunikat, ostatnie linie wyjścia narzędzia + kod 1 (trap EXIT zdejmie tryb serwisowy).
die() {
    echo ""
    fail "$1"
    shift
    for _line in "$@"; do info "$_line"; done
    if [ "$RUN_MARK" -gt 0 ] || [ -s "$LOG" ]; then
        local tail_lines
        tail_lines="$(last_output | grep -v '^\s*$' | tail -n 20 || true)"
        if [ -n "$tail_lines" ] && [ "$VERBOSE" -ne 1 ]; then
            echo ""
            info "Ostatnie linie wyjścia:"
            printf '%s\n' "$tail_lines" | sed "s/^/     ${DIM}${G_V}${NC} /"
        fi
    fi
    mark fail "$CURRENT_STEP"
    exit 1
}

_na_wyjscie() {
    local code=$?
    # Przerwanie w trakcie kroku z animacją: polecenie działa w tle i samo by nie zginęło.
    if [ -n "${RUN_PID:-}" ]; then
        pkill -P "$RUN_PID" 2>/dev/null || true
        kill "$RUN_PID" 2>/dev/null || true
        RUN_PID=""
        printf '\r\033[K'
    fi
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
        printf '\n# wynik: NIEUDANE | krok: %s | czas: %s | koniec: %s\n' "$CURRENT_STEP" "$(since "$DEPLOY_START")" "$(date +%s)" >> "$LOG"
        print_failure "$code"
    fi
    return 0
}
# Podgląd (--demo) pracuje na pliku tymczasowym — usuwamy go przy każdym wyjściu.
_sprzatanie() {
    if [ "$DEMO" -ge 1 ]; then rm -f "$LOG"; fi
    return 0
}
trap '_na_wyjscie; _sprzatanie' EXIT
trap 'exit 130' INT TERM

# ─── Nagłówek ─────────────────────────────────────────────────────────────────

header() {
    local php_ver node_ver
    php_ver="$("$PHP_BIN" -r 'echo PHP_VERSION;' 2>/dev/null || echo '?')"
    node_ver="$("$(dirname "$NPM_BIN")/node" -v 2>/dev/null || node -v 2>/dev/null || echo '?')"
    echo ""
    box_top "$BRAND"
    box_title "$BRAND" "FEER-web $G_DOT $1" "$NSTEPS kroków"
    box_sep "$BRAND"
    box_kv "$BRAND" "Serwer"  "$(hostname 2>/dev/null || echo '?')"
    box_kv "$BRAND" "Katalog" "$(pwd)"
    box_kv "$BRAND" "Gałąź"   "$BRANCH" "$(git rev-parse --short HEAD 2>/dev/null || echo '?')"
    box_kv "$BRAND" "PHP"     "$php_ver" "$PHP_BIN"
    box_kv "$BRAND" "Node"    "$node_ver" "$NPM_BIN"
    if [ "$DRY_RUN" -ne 1 ] && [ "$DEMO" -lt 1 ]; then box_kv "$BRAND" "Log" "$LOG"; fi
    box_bottom "$BRAND"
}

# Lista commitów z `git log` (argumenty po liczbie $1 to zakres), z typem w kolorze, zakresem i czasem.
show_commits() {
    local total="$1" tw=9 rw=14 mw h sj r tag tcol scope rest re
    shift
    mw=$((EDGE - 4 - 8 - 2 - tw - 2 - 1 - rw))
    re='^(feat|fix|refactor|perf|docs|test|style|chore|build|ci|revert)(\(([^)]*)\))?!?:[[:space:]]*(.*)$'
    printf "\n  ${BLD}Zmiany do wdrożenia${NC} ${DIM}%s${NC}\n" "$(commits_pl "$total")"
    printf "  ${DIM}%s${NC}\n" "$(rule "$BOXW")"
    git log --format='%h%x09%s%x09%cr' -n 15 "$@" | while IFS=$'\t' read -r h sj r; do
        tag=""; tcol=""; scope=""; rest="$sj"
        if [[ "$sj" =~ $re ]]; then
            tag="${BASH_REMATCH[1]}"; scope="${BASH_REMATCH[3]}"; rest="${BASH_REMATCH[4]}"
            case "$tag" in
                feat) tcol="$GRN" ;;
                fix) tcol="$RED" ;;
                refactor|perf) tcol="$CYN" ;;
                revert) tcol="$AMB" ;;
                *) tcol="$DIM" ;;
            esac
        fi
        if [ -n "$scope" ]; then rest="$scope $G_ARROW $rest"; fi
        printf "    ${CYN}%s${NC}  ${tcol}%s${NC}  %s ${DIM}%s${NC}\n" "$h" "$(pad "$tag" "$tw")" "$(pad "$(clip "$rest" "$mw")" "$mw")" "$(clip "$r" "$rw")"
    done
    if [ "$total" -gt 15 ]; then info "… i $((total - 15)) starszych"; fi
    return 0
}

# Plan wdrożenia (10 kroków) — wspólny dla --dry-run i --demo.
PLAN=(
    "Tryb serwisowy|artisan down (odwiedzający widzą stronę 503)"
    "Kopia zapasowa baz|SQLite do /tmp"
    "Pobieranie kodu|git pull origin $BRANCH (restart, jeśli zmienił się deploy.sh)"
    "Composer $COMPOSER_CMD|--no-dev --optimize-autoloader"
    "Baza bloga|do $BLOG_DB_NEW (jednorazowo)"
    "Budowanie assetów|npm ci + npm run build"
    "Manifest Vite|$MANIFEST względem vite.config.js"
    "Migracje|główna + blog, kontrola Pending"
    "Cache i optymalizacja|optimize:clear, view:cache, optimize"
    "Kontrola końcowa|system:sprawdz, artisan up, podsumowanie"
)
show_plan() {
    local i=0 entry t d
    printf "\n  ${BLD}Plan wdrożenia${NC}\n"
    printf "  ${DIM}%s${NC}\n" "$(rule "$BOXW")"
    for entry in "${PLAN[@]}"; do
        i=$((i + 1)); t="${entry%%|*}"; d="${entry#*|}"
        printf "   ${BRAND}%2d${NC}  %s ${DIM}%s${NC}\n" "$i" "$(pad "$t" 24)" "$(clip "$d" $((EDGE - 3 - 2 - 2 - 24 - 1)))"
    done
    return 0
}

# ─── Podgląd wyglądu (--demo, --demo-blad) ────────────────────────────────────

# Przykładowy przebieg tego samego kreatora (te same funkcje wyglądu), bez żadnych zmian: poza odczytem listy commitów
# nie woła gita, Composera, npm ani migracji. PHP_BIN to w trakcie podglądu `true`, więc `artisan up` w obsłudze błędu
# niczego nie robi. --demo-blad kończy się błędem w kroku 6, żeby pokazać wygląd przerwanego wdrożenia.
demo_run() {
    local i entry title n=5
    local oks=(
        "Włączony (odwiedzający widzą stronę 503 z ponowieniem za 30 s)."
        "Kopie gotowe."
        "Kod zaktualizowany ($(git rev-parse --short HEAD 2>/dev/null || echo abc1234))."
        "Zależności zainstalowane — Nothing to install, update or remove."
        "Już w $BLOG_DB_NEW."
        "Assety zbudowane (vite: built in 8.42s)."
        "Manifest zawiera wszystkie wejścia z vite.config.js."
        "Migracje zastosowane, brak oczekujących."
        "optimize:clear → cache:clear → view:cache → optimize."
        "Kontroli bez zastrzeżeń: 24."
    )
    header "podgląd wyglądu (nic nie jest zmieniane)"
    PHP_LABEL="$PHP_BIN"
    PHP_BIN=true
    show_commits "$n" -n "$n" HEAD
    confirm "Wdrożyć te $(commits_pl "$n")?"
    for ((i = 1; i <= NSTEPS; i++)); do
        entry="${PLAN[$((i - 1))]}"; title="${entry%%|*}"
        step "$i" "$title"
        if [ "$i" -eq 1 ]; then MAINTENANCE_ON=1; fi
        # DEMO_PAUSE=0 pomija pauzy (testy automatyczne); domyślnie kroki trwają chwilę, żeby było widać spinner.
        case "$i" in
            4|6|8) if [ "${DEMO_PAUSE:-1}" = 1 ]; then run sleep 2; else run true; fi ;;
            *) if [ "${DEMO_PAUSE:-1}" = 1 ]; then sleep 0.4; fi ;;
        esac
        if [ "$DEMO" -eq 2 ] && [ "$i" -eq 6 ]; then
            run bash -c 'echo "> vite build"; echo "error during build:"; echo "[vite]: Rollup failed to resolve import \"./missing.js\" from \"resources/js/app.js\""; exit 1' \
                || die "npm run build nie powiodło się — assety NIE są aktualne." "Uruchom ręcznie: npm run build"
        fi
        case "$i" in
            2) info "Główna       → /tmp/feer-db-demo.sqlite (48M)" ;;
            3) info "3b9c1e2 → $(git rev-parse --short HEAD 2>/dev/null || echo abc1234): 12 files changed, 408 insertions(+), 61 deletions(-)" ;;
            8) info "Baza główna: zastosowano 2"
               info "  $G_DOT 2026_11_07_100000_add_mourning_to_site_settings"
               info "  $G_DOT 2026_11_07_130000_create_demo_table"
               info "Baza bloga: brak nowych migracji" ;;
        esac
        ok "${oks[$((i - 1))]}"
        mark ok "$title"
    done
    STEP_START=$(date +%s)
    MAINTENANCE_ON=0
    mark ok "Tryb serwisowy wyłączony"
    DEPLOY_DONE=1
    print_summary
    print_success
    exit 0
}

if [ "$DEMO" -ge 1 ]; then
    demo_run
fi

# ─── Tryb próbny (--dry-run) ──────────────────────────────────────────────────

if [ "$DRY_RUN" -eq 1 ]; then
    rm -f "$LOG"
    header "tryb próbny (nic nie jest zmieniane)"
    show_plan
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
    printf "\n  ${BRAND}%s${NC} ${BLD}Brak nowych commitów${NC} ${DIM}%s kod na serwerze jest już aktualny (np. po automatycznym pobraniu po pushu).${NC}\n" "$G_OFF" "$G_DOT"
    info "Deploy i tak odświeży zależności, assety, migracje i cache."
    confirm "Kontynuować?"
else
    show_commits "$COUNT" HEAD..origin/"$BRANCH"
    confirm "Wdrożyć $( [ "$COUNT" -eq 1 ] && echo 'ten commit' || echo "te $(commits_pl "$COUNT")" )?"
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
# Skróty z wyjścia narzędzi: brak dopasowania (np. „Już aktualne”) nie może przerwać skryptu (set -e + pipefail).
_stat="$(last_output | grep -E 'files? changed' | tail -n 1 | sed 's/^ *//' || true)"
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
    echo ""
    printf "  ${BRAND}%s${NC} ${BLD}FEER-web ${G_DOT} wdrożenie (ciąg dalszy)${NC}\n" "$G_BAR"
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
_cs="$(grep -E 'Nothing to install|Installing|Updating|Removing|Package operations' "$LOG" | tail -n 1 | sed 's/^ *//; s/^- //' || true)"
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
_built="$(last_output | grep -oE 'built in [0-9.]+ ?m?s' | tail -n 1 || true)"
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
printf '\n# wynik: OK | commit: %s | czas: %s | koniec: %s\n' "$(git rev-parse --short HEAD)" "$(since "$DEPLOY_START")" "$(date +%s)" >> "$LOG"
print_success
