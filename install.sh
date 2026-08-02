#!/usr/bin/env bash
# =============================================================================
#  FEER Web – instalator
#
#  Użycie (jednolinijkowe, bez wcześniejszego klonowania):
#    curl -fsSL https://raw.githubusercontent.com/ziemowit-gil/feer-web/main/install.sh | bash
#
#  Lub po pobraniu skryptu:
#    bash install.sh
#
#  Obsługuje: SQLite i MySQL/MariaDB
#  Wymagania: git, PHP 8.2+, Composer, Node.js 18+
# =============================================================================
set -euo pipefail
IFS=$'\n\t'

REPO_URL="https://github.com/ziemowit-gil/feer-web.git"
REPO_BRANCH="main"

# ── kolory ─────────────────────────────────────────────────────────────────────
RED='\033[0;31m'; YELLOW='\033[0;33m'; GREEN='\033[0;32m'
CYAN='\033[0;36m'; BOLD='\033[1m'; RESET='\033[0m'

ok()   { echo -e "  ${GREEN}✓${RESET}  $*"; }
info() { echo -e "  ${CYAN}→${RESET}  $*"; }
warn() { echo -e "  ${YELLOW}!${RESET}  $*"; }
err()  { echo -e "  ${RED}✗${RESET}  $*" >&2; }
die()  { err "$*"; exit 1; }

header() {
    echo
    echo -e "${BOLD}${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${RESET}"
    echo -e "${BOLD}  $*${RESET}"
    echo -e "${BOLD}${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${RESET}"
    echo
}

ask() {
    local prompt="$1" default="${2:-}"
    if [[ -n "$default" ]]; then
        echo -en "  ${BOLD}$prompt${RESET} [${default}]: "
    else
        echo -en "  ${BOLD}$prompt${RESET}: "
    fi
    read -r REPLY </dev/tty
    echo "${REPLY:-$default}"
}

ask_secret() {
    echo -en "  ${BOLD}$1${RESET}: "
    read -rs REPLY </dev/tty; echo
    echo "$REPLY"
}

confirm() {
    local default="${2:-y}"
    if [[ "$default" == "y" ]]; then
        echo -en "  ${BOLD}$1${RESET} [${GREEN}T${RESET}/n]: "
    else
        echo -en "  ${BOLD}$1${RESET} [t/${RED}N${RESET}]: "
    fi
    read -r ans </dev/tty
    ans="${ans:-$default}"
    [[ "$ans" =~ ^[TtYy]$ ]]
}

env_set() {
    local key="$1" val="$2"
    if grep -q "^${key}=" .env 2>/dev/null; then
        sed -i.bak "s|^${key}=.*|${key}=${val}|" .env && rm -f .env.bak
    else
        printf '%s=%s\n' "$key" "$val" >> .env
    fi
}

env_set_quoted() { env_set "$1" "\"$2\""; }

# ── wykryj binarki ─────────────────────────────────────────────────────────────
PHP_BIN=""
for candidate in php85 php8.5 php8.4 php8.3 php8.2 php; do
    if command -v "$candidate" &>/dev/null; then
        ver=$("$candidate" -r 'echo PHP_VERSION_ID;' 2>/dev/null || echo 0)
        if (( ver >= 80200 )); then PHP_BIN="$candidate"; break; fi
    fi
done

COMPOSER_BIN=""
for candidate in composer composer.phar; do
    command -v "$candidate" &>/dev/null && { COMPOSER_BIN="$candidate"; break; }
done

NODE_BIN=$(command -v node 2>/dev/null || true)
NPM_BIN=$(command -v npm 2>/dev/null || true)
GIT_BIN=$(command -v git 2>/dev/null || true)

# ── baner ──────────────────────────────────────────────────────────────────────
clear 2>/dev/null || true
echo
echo -e "${BOLD}${CYAN}"
echo "  ███████╗███████╗███████╗██████╗ "
echo "  ██╔════╝██╔════╝██╔════╝██╔══██╗"
echo "  █████╗  █████╗  █████╗  ██████╔╝"
echo "  ██╔══╝  ██╔══╝  ██╔══╝  ██╔══██╗"
echo "  ██║     ███████╗███████╗██║  ██║"
echo "  ╚═╝     ╚══════╝╚══════╝╚═╝  ╚═╝"
echo -e "${RESET}"
echo -e "  ${BOLD}Instalator FEER Web${RESET} — panel CMS dla organizacji pozarządowych"
echo

# ── wymagania ──────────────────────────────────────────────────────────────────
header "Sprawdzanie wymagań"

[[ -z "$GIT_BIN" ]]      && die "git nie znaleziony. Zainstaluj git i spróbuj ponownie."
ok "git: $(git --version | head -1)"

[[ -z "$PHP_BIN" ]]      && die "Nie znaleziono PHP 8.2+. Zainstaluj PHP i spróbuj ponownie."
ok "PHP: $($PHP_BIN -r 'echo PHP_VERSION;') ($PHP_BIN)"

[[ -z "$COMPOSER_BIN" ]] && die "Composer nie znaleziony. Zainstaluj z https://getcomposer.org"
ok "Composer: $($COMPOSER_BIN --version --no-ansi | head -1)"

BUILD_FRONTEND=false
if [[ -z "$NODE_BIN" ]]; then
    warn "Node.js nie znaleziony — assets frontend nie zostaną zbudowane."
else
    node_ver=$("$NODE_BIN" --version)
    node_major="${node_ver#v}"; node_major="${node_major%%.*}"
    (( node_major < 18 )) && warn "Node.js $node_ver jest starszy niż 18." || ok "Node.js: $node_ver"
    BUILD_FRONTEND=true
fi

# ── klonowanie repo ────────────────────────────────────────────────────────────
# Jeśli skrypt uruchomiony spoza katalogu projektu, klonuje repo.
# Jeśli już jesteśmy wewnątrz projektu (artisan istnieje), pomijamy klonowanie.

if [[ ! -f "artisan" ]]; then
    header "Pobieranie projektu z repozytorium"
    info "Repo: ${REPO_URL}"
    echo

    INSTALL_DIR=$(ask "Katalog instalacji" "feer-web")

    if [[ -d "$INSTALL_DIR" && -n "$(ls -A "$INSTALL_DIR" 2>/dev/null)" ]]; then
        warn "Katalog '$INSTALL_DIR' już istnieje i nie jest pusty."
        if confirm "Usunąć i sklonować od nowa?" "n"; then
            rm -rf "$INSTALL_DIR"
        else
            die "Instalacja przerwana. Usuń katalog lub wybierz inny."
        fi
    fi

    git clone --branch "$REPO_BRANCH" --depth 1 "$REPO_URL" "$INSTALL_DIR"
    ok "Projekt pobrany do: $INSTALL_DIR"
    cd "$INSTALL_DIR"
else
    ok "Projekt już pobrany — kontynuuję konfigurację w bieżącym katalogu."
fi

PROJECT_DIR="$(pwd)"

# ── plik .env ──────────────────────────────────────────────────────────────────
header "Konfiguracja środowiska"

if [[ -f ".env" ]]; then
    warn "Plik .env już istnieje."
    if confirm "Nadpisać istniejącą konfigurację?" "n"; then
        cp .env ".env.backup.$(date +%Y%m%d%H%M%S)"
        ok "Kopia zapasowa zapisana."
        cp .env.example .env
        CONFIGURE_ENV=true
    else
        info "Pomijam konfigurację .env — używam istniejącego pliku."
        CONFIGURE_ENV=false
    fi
else
    cp .env.example .env
    CONFIGURE_ENV=true
fi

if [[ "$CONFIGURE_ENV" == "true" ]]; then
    APP_URL=$(ask  "URL aplikacji (bez końcowego /)" "http://localhost")
    APP_NAME=$(ask "Nazwa aplikacji (wyświetlana w panelu)" "FEER Web")
    APP_ENV=$(ask  "Środowisko" "local")

    env_set_quoted "APP_NAME"  "$APP_NAME"
    env_set        "APP_URL"   "$APP_URL"
    env_set        "APP_ENV"   "$APP_ENV"
    env_set        "APP_DEBUG" "$([[ "$APP_ENV" == "production" ]] && echo false || echo true)"
    echo
fi

# ── baza danych ────────────────────────────────────────────────────────────────
header "Konfiguracja bazy danych"

echo -e "  Wybierz typ bazy danych:"
echo -e "    ${BOLD}1${RESET}) SQLite           ${CYAN}— łatwa instalacja, zalecana lokalnie${RESET}"
echo -e "    ${BOLD}2${RESET}) MySQL / MariaDB  ${CYAN}— zalecana na produkcji${RESET}"
echo
DB_CHOICE=$(ask "Wybór" "1")

case "$DB_CHOICE" in
  2)
    DB_TYPE="mysql"
    echo
    DB_HOST=$(ask "Host MySQL"          "127.0.0.1")
    DB_PORT=$(ask "Port MySQL"          "3306")
    DB_NAME=$(ask "Nazwa bazy danych"   "feer_web")
    DB_USER=$(ask "Użytkownik MySQL"    "root")
    DB_PASS=$(ask_secret "Hasło MySQL")

    # Usuń komentarz z opcji MySQL i ustaw wartości
    sed -i.bak 's|^# DB_HOST=.*|DB_HOST=127.0.0.1|; s|^# DB_PORT=.*|DB_PORT=3306|; s|^# DB_DATABASE=.*|DB_DATABASE=laravel|; s|^# DB_USERNAME=.*|DB_USERNAME=root|; s|^# DB_PASSWORD=.*|DB_PASSWORD=|' .env && rm -f .env.bak
    env_set "DB_CONNECTION" "mysql"
    env_set "DB_HOST"       "$DB_HOST"
    env_set "DB_PORT"       "$DB_PORT"
    env_set "DB_DATABASE"   "$DB_NAME"
    env_set "DB_USERNAME"   "$DB_USER"
    env_set "DB_PASSWORD"   "$DB_PASS"

    echo
    info "Próba połączenia z MySQL i utworzenia bazy..."
    MYSQL_OPTS=(-h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER")
    [[ -n "$DB_PASS" ]] && MYSQL_OPTS+=(-p"$DB_PASS")

    if command -v mysql &>/dev/null; then
        if mysql "${MYSQL_OPTS[@]}" \
            -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null; then
            ok "Baza danych '${DB_NAME}' gotowa."
        else
            warn "Nie udało się automatycznie utworzyć bazy. Utwórz ją ręcznie:"
            echo
            echo -e "  ${CYAN}CREATE DATABASE \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;${RESET}"
            echo
            confirm "Kontynuować po ręcznym utworzeniu bazy?" "y" || die "Instalacja przerwana."
        fi
    else
        warn "Klient mysql nie znaleziony — utwórz bazę ręcznie przed kontynuacją."
        confirm "Kontynuować?" "y" || die "Instalacja przerwana."
    fi
    ;;
  *)
    DB_TYPE="sqlite"
    touch "${PROJECT_DIR}/database/database.sqlite"
    env_set "DB_CONNECTION" "sqlite"
    # Zakomentuj linie MySQL, żeby nie mylić
    sed -i.bak 's|^DB_HOST=|# DB_HOST=|; s|^DB_PORT=|# DB_PORT=|; s|^DB_DATABASE=|# DB_DATABASE=|; s|^DB_USERNAME=|# DB_USERNAME=|; s|^DB_PASSWORD=|# DB_PASSWORD=|' .env && rm -f .env.bak
    ok "SQLite: database/database.sqlite"
    ;;
esac

# ── blog SQLite ────────────────────────────────────────────────────────────────
echo
if confirm "Skonfigurować bazę SQLite dla blogu 'Wiem FEER'?" "n"; then
    BLOG_DB="${PROJECT_DIR}/database/blog.sqlite"
    touch "$BLOG_DB"
    env_set "BLOG_DB_DATABASE" "$BLOG_DB"
    ok "Blog SQLite: database/blog.sqlite"
else
    info "Blog pomięty — ustaw BLOG_DB_DATABASE w .env gdy potrzebny."
fi

# ── Composer ───────────────────────────────────────────────────────────────────
header "Instalacja zależności PHP (Composer)"

APP_ENV_VAL=$(grep "^APP_ENV=" .env | cut -d'=' -f2 | tr -d '"')
if [[ "$APP_ENV_VAL" == "production" ]]; then
    $COMPOSER_BIN install --no-dev --optimize-autoloader --no-interaction
else
    $COMPOSER_BIN install --no-interaction
fi
ok "Zależności PHP zainstalowane."

# ── klucz aplikacji ────────────────────────────────────────────────────────────
APP_KEY_VAL=$(grep "^APP_KEY=" .env | cut -d'=' -f2)
if [[ -z "$APP_KEY_VAL" || "$APP_KEY_VAL" == '""' ]]; then
    $PHP_BIN artisan key:generate --force
    ok "Klucz aplikacji wygenerowany."
else
    ok "Klucz aplikacji już ustawiony."
fi

# ── Node.js / frontend ─────────────────────────────────────────────────────────
if [[ "$BUILD_FRONTEND" == "true" ]]; then
    header "Budowanie assetów frontend (npm)"
    $NPM_BIN install --prefer-offline
    $NPM_BIN run build
    ok "Assets frontend zbudowane."
fi

# ── migracje ───────────────────────────────────────────────────────────────────
header "Migracje bazy danych"

$PHP_BIN artisan migrate --force
ok "Migracje wykonane."

BLOG_DB_VAL=$(grep "^BLOG_DB_DATABASE=" .env | cut -d'=' -f2 | tr -d '"')
if [[ -n "$BLOG_DB_VAL" ]]; then
    $PHP_BIN artisan migrate --database=blog --path=database/migrations/blog --force 2>/dev/null \
        && ok "Migracje bloga wykonane." \
        || warn "Migracje bloga nie powiodły się — sprawdź konfigurację."
fi

# ── storage:link ───────────────────────────────────────────────────────────────
$PHP_BIN artisan storage:link --force 2>/dev/null && ok "Symlink public/storage → storage/app/public." || true

# ── dane demo ──────────────────────────────────────────────────────────────────
header "Dane startowe"

echo -e "  Seeder wypełni bazę przykładowymi danymi:"
echo -e "  użytkownicy demo, ustawienia organizacji, dokumenty BIP, FAQ i inne."
echo
if confirm "Załadować przykładowe dane demo?" "y"; then
    $PHP_BIN artisan db:seed --force
    ok "Dane demo załadowane."
    echo
    echo -e "  ${BOLD}Konta demo${RESET} (hasło: ${CYAN}demo12(@${RESET}):"
    printf "    %-35s %s\n" "admin@demo.feer.org.pl"    "— Administrator"
    printf "    %-35s %s\n" "redaktor@demo.feer.org.pl"  "— Edytor"
    printf "    %-35s %s\n" "bip@demo.feer.org.pl"       "— Edytor BIP"
else
    info "Seeder pominięty."
fi

# ── klucze VAPID (Web Push) ────────────────────────────────────────────────────
VAPID_VAL=$(grep "^VAPID_PUBLIC_KEY=" .env | cut -d'=' -f2)
if [[ -z "$VAPID_VAL" ]]; then
    echo
    if confirm "Wygenerować klucze VAPID dla powiadomień push?" "y"; then
        VAPID_JSON=$($PHP_BIN artisan tinker \
            --execute='echo json_encode(Minishlink\WebPush\VAPID::createVapidKeys());' \
            2>/dev/null | tail -1 || true)
        VAPID_PUB=$(echo "$VAPID_JSON" | $PHP_BIN -r 'echo json_decode(file_get_contents("php://stdin"))->publicKey ?? "";' 2>/dev/null || true)
        VAPID_PRV=$(echo "$VAPID_JSON" | $PHP_BIN -r 'echo json_decode(file_get_contents("php://stdin"))->privateKey ?? "";' 2>/dev/null || true)
        if [[ -n "$VAPID_PUB" && -n "$VAPID_PRV" ]]; then
            env_set "VAPID_PUBLIC_KEY"  "$VAPID_PUB"
            env_set "VAPID_PRIVATE_KEY" "$VAPID_PRV"
            ok "Klucze VAPID zapisane w .env."
        else
            warn "Nie udało się wygenerować kluczy VAPID — ustaw ręcznie w .env."
        fi
    fi
fi

# ── uprawnienia (Linux) ────────────────────────────────────────────────────────
if [[ "$(uname -s)" == "Linux" ]]; then
    echo
    if confirm "Ustawić uprawnienia 775 do storage/ i bootstrap/cache/?" "y"; then
        chmod -R 775 storage bootstrap/cache
        ok "Uprawnienia ustawione."
    fi
fi

# ── podsumowanie ───────────────────────────────────────────────────────────────
header "Instalacja zakończona ✓"

APP_URL_VAL=$(grep "^APP_URL=" .env | cut -d'=' -f2)

echo -e "  ${GREEN}${BOLD}Gotowe!${RESET} Aplikacja zainstalowana w:"
echo -e "  ${CYAN}${PROJECT_DIR}${RESET}"
echo
echo -e "  ${BOLD}Panel administracyjny:${RESET}  ${CYAN}${APP_URL_VAL}/admin${RESET}"
echo -e "  ${BOLD}Baza danych:${RESET}            ${DB_TYPE}"
echo
echo -e "  ${BOLD}Następne kroki:${RESET}"
echo -e "    ${CYAN}1.${RESET} E-mail: ustaw MAIL_* w .env (np. SMTP Mailpit do testów)"
echo -e "    ${CYAN}2.${RESET} Opcjonalnie: Microsoft 365 SSO → MICROSOFT_CLIENT_ID, ..."
echo -e "    ${CYAN}3.${RESET} Uzupełnij dane organizacji: ${APP_URL_VAL}/admin/ustawienia"
[[ "$BUILD_FRONTEND" == "false" ]] && \
echo -e "    ${YELLOW}!${RESET} Zbuduj frontend: npm install && npm run build"
[[ "$APP_ENV_VAL" == "production" ]] && \
echo -e "    ${CYAN}4.${RESET} Na produkcji uruchom: $PHP_BIN artisan queue:work"
echo
