#!/usr/bin/env bash
#
# server-setup.sh — „domknięcie" instalacji FEER po przeniesieniu jej na nowy
# serwer/hosting (pliki są już na miejscu). Wykonuje typowe czynności Laravela:
# .env i klucz aplikacji, zależności (Composer + npm), budowanie zasobów,
# względny symlink public/storage, plik bazy SQLite, uprawnienia do zapisu,
# migracje i cache. Skrypt sam wykrywa katalog projektu (leży w cli/).
#
# Użycie:
#   cli/server-setup.sh [opcje]
#
# Opcje:
#   -y, --yes          nie pytaj o potwierdzenie
#       --seed         po migracjach uruchom php artisan db:seed
#       --force-key    wygeneruj APP_KEY nawet jeśli już jest ustawiony
#       --no-composer  pomiń composer install
#       --no-build     pomiń npm install + npm run build
#       --no-cache     tylko wyczyść cache, nie buduj go na nowo
#   -h, --help         pomoc
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
DO_SEED=0
FORCE_KEY=0
DO_COMPOSER=1
DO_BUILD=1
DO_CACHE=1

# --- Parsowanie argumentów ---------------------------------------------------
while [[ $# -gt 0 ]]; do
    case "$1" in
        -y|--yes) ASSUME_YES=1; shift ;;
        --seed) DO_SEED=1; shift ;;
        --force-key) FORCE_KEY=1; shift ;;
        --no-composer) DO_COMPOSER=0; shift ;;
        --no-build) DO_BUILD=0; shift ;;
        --no-cache) DO_CACHE=0; shift ;;
        -h|--help) sed -n '2,30p' "$0"; exit 0 ;;
        *) echo "Nieznana opcja: $1" >&2; exit 1 ;;
    esac
done

# --- Walidacja ---------------------------------------------------------------
[[ -f artisan ]] || { echo "Brak pliku artisan — czy to na pewno projekt Laravela?" >&2; exit 1; }

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
echo " Konfiguracja FEER na nowym serwerze"
echo "   katalog: $ROOT"
echo "=============================================================="

if [[ "$ASSUME_YES" -ne 1 ]]; then
    read -r -p "Kontynuować? [t/N] " ans
    [[ "$ans" =~ ^([tT]|[yY])$ ]] || { echo "Przerwano."; exit 0; }
fi

# --- 1. Plik .env ------------------------------------------------------------
if [[ ! -f .env ]]; then
    if [[ -f .env.example ]]; then
        cp .env.example .env
        echo "✔ Utworzono .env z .env.example — UZUPEŁNIJ dane (domena, poczta, baza)."
    else
        echo "⚠ Brak .env i .env.example — utwórz .env ręcznie." >&2
    fi
fi

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

# --- 3. Klucz aplikacji ------------------------------------------------------
if [[ -f .env ]]; then
    CURRENT_KEY="$(grep -E '^APP_KEY=' .env | head -n1 | cut -d= -f2- || true)"
    if [[ "$FORCE_KEY" -eq 1 || -z "$CURRENT_KEY" || "$CURRENT_KEY" == "base64:" ]]; then
        "$PHP_BIN" artisan key:generate --force
        echo "✔ Wygenerowano APP_KEY."
    fi
fi

# --- 4. Baza danych SQLite (jeśli używana) -----------------------------------
DB_CONN="$(grep -E '^DB_CONNECTION=' .env 2>/dev/null | head -n1 | cut -d= -f2- || true)"
if [[ "$DB_CONN" == "sqlite" || ( -z "$DB_CONN" && -f database/database.sqlite ) ]]; then
    if [[ ! -f database/database.sqlite ]]; then
        touch database/database.sqlite
        echo "✔ Utworzono pusty plik bazy database/database.sqlite."
    fi
fi

# --- 5. Uprawnienia do zapisu ------------------------------------------------
chmod -R ug+rwX storage bootstrap/cache 2>/dev/null \
    && echo "✔ Ustawiono prawa zapisu na storage/ i bootstrap/cache." \
    || echo "⚠ Nie udało się ustawić praw na storage/ — sprawdź uprawnienia ręcznie." >&2

# --- 6. Front-end (npm + Vite) -----------------------------------------------
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

# --- 7. Symlink public/storage (względny) ------------------------------------
if [[ -L public/storage ]]; then
    rm -f public/storage
elif [[ -d public/storage ]]; then
    mv public/storage "public/storage.bak_$(date +%F_%H%M%S)"
    echo "⚠ public/storage było katalogiem — przeniesiono do public/storage.bak_*." >&2
fi
# Symlink WZGLĘDNY (../storage/app/public) — niezależny od ścieżki bezwzględnej,
# nie psuje się po zmianie serwera/katalogu. Tworzymy go wprost przez `ln -s`
# (artisan storage:link --relative wymaga pakietu symfony/filesystem).
ln -s ../storage/app/public public/storage
echo "✔ Odtworzono symlink public/storage (względny)."

# --- 8. Migracje -------------------------------------------------------------
"$PHP_BIN" artisan migrate --force
echo "✔ Migracje wykonane."

if [[ "$DO_SEED" -eq 1 ]]; then
    "$PHP_BIN" artisan db:seed --force
    echo "✔ Uruchomiono seedowanie bazy."
fi

# --- 9. Cache ----------------------------------------------------------------
"$PHP_BIN" artisan optimize:clear
if [[ "$DO_CACHE" -eq 1 ]]; then
    "$PHP_BIN" artisan config:cache
    "$PHP_BIN" artisan route:cache
    "$PHP_BIN" artisan view:cache
    echo "✔ Zbudowano cache (config, route, view)."
else
    echo "✔ Wyczyszczono cache."
fi

echo "=============================================================="
echo " Gotowe. Sprawdź .env i otwórz stronę w przeglądarce."
echo "=============================================================="
