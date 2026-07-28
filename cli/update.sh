#!/usr/bin/env bash
#
# update.sh — aktualizacja działającej instalacji FEER z repozytorium git.
# Pobiera zmiany, instaluje zależności (Composer + npm), buduje zasoby (Vite),
# domyka migracje i odświeża cache. Uruchamiaj w katalogu z instalacją (skrypt
# sam wykrywa katalog projektu na podstawie własnego położenia w cli/).
#
# Użycie:
#   cli/update.sh [opcje]
#
# Opcje:
#   -y, --yes            nie pytaj o potwierdzenie
#   -b, --branch X       gałąź do zaktualizowania (domyślnie bieżąca)
#       --hard           git reset --hard origin/<gałąź> zamiast pull --ff-only
#                        (nadpisuje lokalne zmiany — używaj świadomie)
#       --maintenance    na czas aktualizacji włącz tryb konserwacji (artisan down/up)
#       --no-composer    pomiń composer install
#       --no-build       pomiń npm install + npm run build
#       --no-migrate     pomiń php artisan migrate
#       --no-cache       tylko wyczyść cache, nie buduj go na nowo
#   -h, --help           pomoc
#
# Zmienne środowiskowe:
#   PHP_BIN       ścieżka do PHP       (domyślnie „php")
#   COMPOSER_BIN  polecenie Composera  (domyślnie wykrywane: composer / composer.phar)
#   NPM_BIN       ścieżka do npm       (domyślnie wykrywana automatycznie)
#
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

PHP_BIN="${PHP_BIN:-php}"

ASSUME_YES=0
DO_COMPOSER=1
DO_BUILD=1
DO_MIGRATE=1
DO_CACHE=1
HARD=0
MAINTENANCE=0
BRANCH=""

# --- Parsowanie argumentów ---------------------------------------------------
while [[ $# -gt 0 ]]; do
    case "$1" in
        -y|--yes) ASSUME_YES=1; shift ;;
        -b|--branch) BRANCH="$2"; shift 2 ;;
        --hard) HARD=1; shift ;;
        --maintenance) MAINTENANCE=1; shift ;;
        --no-composer) DO_COMPOSER=0; shift ;;
        --no-build) DO_BUILD=0; shift ;;
        --no-migrate) DO_MIGRATE=0; shift ;;
        --no-cache) DO_CACHE=0; shift ;;
        -h|--help) sed -n '2,32p' "$0"; exit 0 ;;
        *) echo "Nieznana opcja: $1" >&2; exit 1 ;;
    esac
done

# --- Walidacja ---------------------------------------------------------------
command -v git >/dev/null || { echo "Brak git w PATH." >&2; exit 1; }
git rev-parse --is-inside-work-tree >/dev/null 2>&1 || { echo "To nie jest repozytorium git: $ROOT" >&2; exit 1; }
[[ -f artisan ]] || { echo "Brak pliku artisan — czy to na pewno projekt Laravela?" >&2; exit 1; }

BRANCH="${BRANCH:-$(git rev-parse --abbrev-ref HEAD)}"

# Wykrywanie npm (zmienna, PATH, typowe lokalizacje na hostingu współdzielonym).
detect_npm() {
    NPM_BIN="${NPM_BIN:-}"
    if [[ -z "$NPM_BIN" ]] && command -v npm >/dev/null 2>&1; then NPM_BIN="$(command -v npm)"; fi
    if [[ -z "$NPM_BIN" ]]; then
        for candidate in \
            "$HOME"/nodevenv/*/*/bin/npm \
            "$HOME"/.nodevenv/*/*/bin/npm \
            "$HOME"/.nvm/versions/node/*/bin/npm \
            /usr/local/bin/npm /usr/bin/npm /opt/*/bin/npm; do
            if [[ -x "$candidate" ]]; then NPM_BIN="$candidate"; break; fi
        done
    fi
}

# Wykrywanie Composera (zmienna, composer w PATH, lokalny composer.phar).
detect_composer() {
    COMPOSER_BIN="${COMPOSER_BIN:-}"
    if [[ -z "$COMPOSER_BIN" ]]; then
        if command -v composer >/dev/null 2>&1; then
            COMPOSER_BIN="composer"
        elif [[ -f composer.phar ]]; then
            COMPOSER_BIN="$PHP_BIN composer.phar"
        fi
    fi
}

echo "=============================================================="
echo " Aktualizacja FEER z git"
echo "   katalog: $ROOT"
echo "   gałąź:   $BRANCH"
echo "   tryb:    $([[ $HARD -eq 1 ]] && echo 'reset --hard' || echo 'pull --ff-only')"
echo "=============================================================="

if [[ "$ASSUME_YES" -ne 1 ]]; then
    read -r -p "Kontynuować aktualizację? [t/N] " ans
    [[ "$ans" =~ ^([tT]|[yY])$ ]] || { echo "Przerwano."; exit 0; }
fi

# --- Tryb konserwacji --------------------------------------------------------
if [[ "$MAINTENANCE" -eq 1 ]]; then
    "$PHP_BIN" artisan down --render="errors::503" >/dev/null 2>&1 || "$PHP_BIN" artisan down || true
    # Bez względu na to, jak skończy się skrypt, zdejmij tryb konserwacji.
    trap '"$PHP_BIN" artisan up >/dev/null 2>&1 || true' EXIT
    echo "✔ Włączono tryb konserwacji."
fi

# --- 1. Pobranie zmian z git -------------------------------------------------
git fetch --prune origin
if [[ "$HARD" -eq 1 ]]; then
    git reset --hard "origin/$BRANCH"
else
    git pull --ff-only origin "$BRANCH"
fi
echo "✔ Pobrano zmiany (git, gałąź $BRANCH)."

# --- 2. Zależności PHP (Composer) --------------------------------------------
if [[ "$DO_COMPOSER" -eq 1 && -f composer.json ]]; then
    detect_composer
    if [[ -z "$COMPOSER_BIN" ]]; then
        echo "⚠ Nie znaleziono Composera — pomijam instalację zależności PHP." >&2
        echo "  Zainstaluj ręcznie: composer install --no-dev --optimize-autoloader" >&2
    else
        $COMPOSER_BIN install --no-interaction --prefer-dist --no-dev --optimize-autoloader
        echo "✔ Zainstalowano zależności PHP (Composer)."
    fi
fi

# --- 3. Zależności front-end i budowanie (npm + Vite) ------------------------
if [[ "$DO_BUILD" -eq 1 && -f package.json ]]; then
    detect_npm
    if [[ -z "$NPM_BIN" ]] || ! "$NPM_BIN" --version >/dev/null 2>&1; then
        echo "⚠ Nie znaleziono działającego npm — pomijam budowanie front-endu." >&2
        echo "  Ustaw NPM_BIN=/ścieżka/do/npm lub zbuduj ręcznie: npm install && npm run build" >&2
    else
        export PATH="$(dirname "$NPM_BIN"):$PATH"
        echo "→ npm: $NPM_BIN (v$("$NPM_BIN" --version 2>/dev/null || echo '?'))"
        "$NPM_BIN" install --no-audit --no-fund --ignore-scripts
        "$NPM_BIN" run build
        echo "✔ Zainstalowano zależności npm i zbudowano zasoby (Vite)."
    fi
fi

# --- 4. Migracje bazy --------------------------------------------------------
if [[ "$DO_MIGRATE" -eq 1 ]]; then
    "$PHP_BIN" artisan migrate --force
    echo "✔ Migracje zaktualizowane."
fi

# --- 5. Symlink public/storage (względny — przetrwa przeniesienie instalacji) -
link_storage() {
    if [[ -L public/storage ]]; then
        rm -f public/storage
    elif [[ -d public/storage ]]; then
        # Prawdziwy katalog w miejscu symlinku (np. rozpakowany na hostingu) —
        # odsuń go do backupu zamiast kasować, żeby nie stracić plików.
        mv public/storage "public/storage.bak_$(date +%F_%H%M%S)"
        echo "⚠ public/storage było katalogiem — przeniesiono do public/storage.bak_*." >&2
    fi
    # Symlink WZGLĘDNY (../storage/app/public) — niezależny od bezwzględnej
    # ścieżki instalacji, więc nie psuje się po kopii/zmianie serwera. Tworzymy
    # go wprost przez `ln -s` (artisan storage:link --relative wymaga pakietu
    # symfony/filesystem, którego projekt nie ma).
    ln -s ../storage/app/public public/storage
}
link_storage
echo "✔ Odtworzono symlink public/storage (względny)."

# --- 6. Cache ----------------------------------------------------------------
"$PHP_BIN" artisan optimize:clear
if [[ "$DO_CACHE" -eq 1 ]]; then
    "$PHP_BIN" artisan config:cache
    "$PHP_BIN" artisan route:cache
    "$PHP_BIN" artisan view:cache
    echo "✔ Odświeżono cache (config, route, view)."
else
    echo "✔ Wyczyszczono cache."
fi

echo "=============================================================="
echo " Gotowe. Aktualizacja zakończona."
echo "=============================================================="
