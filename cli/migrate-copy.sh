#!/usr/bin/env bash
#
# migrate-copy.sh — kopia 1:1 instalacji FEER między dwoma katalogami na tym
# samym serwerze (np. beta → produkcja), wraz z poprawkami specyficznymi dla
# Laravela: odtworzenie symlinku storage, korekta domeny w .env, wyczyszczenie
# cache i domknięcie migracji.
#
# Użycie:
#   cli/migrate-copy.sh [ŹRÓDŁO] [CEL] [opcje]
#
# Domyślne ścieżki (można nadpisać argumentami pozycyjnymi):
#   ŹRÓDŁO = /home/srv100843/domains/beta.feer.org.pl/feer-web
#   CEL    = /home/srv100843/domains/feer.org.pl/feer-web
#
# Opcje:
#   -n, --dry-run          tylko podgląd (rsync -n), nic nie zmienia
#   -y, --yes              nie pytaj o potwierdzenie
#       --no-delete        nie usuwaj w celu plików spoza źródła (bez rsync --delete)
#       --no-build         pomiń instalację npm i budowanie zasobów (Vite)
#       --replace-db-urls  zamień w bazie (SQLite) pełne URL-e ze starą domeną
#       --old-domain X     stara domena (domyślnie beta.feer.org.pl)
#       --new-domain Y     nowa domena (domyślnie feer.org.pl)
#   -h, --help             pomoc
#
# Zmienne środowiskowe:
#   PHP_BIN   ścieżka do PHP  (domyślnie „php")
#   NPM_BIN   ścieżka do npm  (domyślnie wykrywana automatycznie — patrz krok 2b)
#
set -euo pipefail

SRC="/home/srv100843/domains/beta.feer.org.pl/feer-web"
DEST="/home/srv100843/domains/feer.org.pl/feer-web"
OLD_DOMAIN="beta.feer.org.pl"
NEW_DOMAIN="feer.org.pl"
PHP_BIN="${PHP_BIN:-php}"

DRY_RUN=0
ASSUME_YES=0
USE_DELETE=1
REPLACE_DB_URLS=0
BUILD=1

# --- Parsowanie argumentów ---------------------------------------------------
POSITIONAL=()
while [[ $# -gt 0 ]]; do
    case "$1" in
        -n|--dry-run) DRY_RUN=1; shift ;;
        -y|--yes) ASSUME_YES=1; shift ;;
        --no-delete) USE_DELETE=0; shift ;;
        --no-build) BUILD=0; shift ;;
        --replace-db-urls) REPLACE_DB_URLS=1; shift ;;
        --old-domain) OLD_DOMAIN="$2"; shift 2 ;;
        --new-domain) NEW_DOMAIN="$2"; shift 2 ;;
        -h|--help) sed -n '2,28p' "$0"; exit 0 ;;
        -*) echo "Nieznana opcja: $1" >&2; exit 1 ;;
        *) POSITIONAL+=("$1"); shift ;;
    esac
done
[[ ${#POSITIONAL[@]} -ge 1 ]] && SRC="${POSITIONAL[0]}"
[[ ${#POSITIONAL[@]} -ge 2 ]] && DEST="${POSITIONAL[1]}"

# Znormalizuj (bez końcowego ukośnika — dodajemy go świadomie w rsync).
SRC="${SRC%/}"
DEST="${DEST%/}"

# --- Walidacja ---------------------------------------------------------------
command -v rsync >/dev/null || { echo "Brak rsync w PATH." >&2; exit 1; }
[[ -d "$SRC" ]] || { echo "Źródło nie istnieje: $SRC" >&2; exit 1; }
[[ "$SRC" != "$DEST" ]] || { echo "Źródło i cel są takie same." >&2; exit 1; }

DELETE_FLAG=""
[[ "$USE_DELETE" -eq 1 ]] && DELETE_FLAG="--delete"

echo "=============================================================="
echo " Kopia 1:1 instalacji FEER"
echo "   źródło:  $SRC/"
echo "   cel:     $DEST/"
echo "   domena:  $OLD_DOMAIN  ->  $NEW_DOMAIN"
echo "   rsync:   -a $DELETE_FLAG$([[ $DRY_RUN -eq 1 ]] && echo ' -n (dry-run)')"
echo "=============================================================="

if [[ "$ASSUME_YES" -ne 1 && "$DRY_RUN" -ne 1 ]]; then
    read -r -p "Kontynuować? Cel zostanie nadpisany. [t/N] " ans
    [[ "$ans" =~ ^([tT]|[yY])$ ]] || { echo "Przerwano."; exit 0; }
fi

# --- 1. Kopiowanie -----------------------------------------------------------
mkdir -p "$DEST"
if [[ "$DRY_RUN" -eq 1 ]]; then
    echo "== Podgląd (dry-run) — żadne pliki nie zostaną zmienione =="
    rsync -avn $DELETE_FLAG "$SRC/" "$DEST/"
    if [[ "$BUILD" -eq 1 ]]; then
        echo "== Po skopiowaniu nastąpiłoby: npm install + npm run build (Vite). Wyłącz opcją --no-build. =="
    fi
    echo "== Koniec podglądu. Uruchom bez --dry-run, aby wykonać. =="
    exit 0
fi

rsync -a $DELETE_FLAG "$SRC/" "$DEST/"
echo "✔ Skopiowano pliki."

# --- 2. Poprawki Laravela w katalogu docelowym -------------------------------
cd "$DEST"

# a) Symlink public/storage wskazywał na starą (bezwzględną) ścieżkę źródła —
#    odtwórz jako symlink WZGLĘDNY, żeby wskazywał na storage w katalogu celu
#    i nie psuł się przy kolejnych przenosinach.
if [[ -f artisan ]]; then
    if [[ -L public/storage ]]; then
        rm -f public/storage
    elif [[ -d public/storage ]]; then
        mv public/storage "public/storage.bak_$(date +%F_%H%M%S)"
        echo "⚠ public/storage było katalogiem — przeniesiono do public/storage.bak_*." >&2
    fi
    # Symlink WZGLĘDNY przez `ln -s` (artisan storage:link --relative wymaga
    # pakietu symfony/filesystem, którego projekt nie ma).
    ln -s ../storage/app/public public/storage
    echo "✔ Odtworzono symlink public/storage (względny)."
fi

# b) Korekta domeny w .env (jedyny plik świadomie NIE 1:1).
if [[ -f .env ]]; then
    sed -i "s#${OLD_DOMAIN//./\\.}#${NEW_DOMAIN}#g" .env
    echo "✔ Zaktualizowano domenę w .env ($OLD_DOMAIN → $NEW_DOMAIN)."
fi

# c) Wyczyść cache konfiguracji/tras/widoków (mogły zapisać stare ścieżki/URL).
if [[ -f artisan ]]; then
    "$PHP_BIN" artisan optimize:clear
    echo "✔ Wyczyszczono cache."

    # d) Domknięcie migracji (addytywne — baza przyszła z kopią).
    "$PHP_BIN" artisan migrate --force
    echo "✔ Migracje zaktualizowane."
fi

# --- 2b. Instalacja zależności i budowanie zasobów front-end (npm + Vite) ----
# public/build kopiuje się 1:1 z rsync, ale przebudowa w katalogu docelowym
# gwarantuje, że manifest i pliki z hashami odpowiadają aktualnym źródłom.
if [[ "$BUILD" -eq 1 && -f package.json ]]; then
    # Wykryj npm: najpierw zmienna NPM_BIN, potem PATH, na końcu typowe lokalizacje
    # na hostingach współdzielonych (selektor Node.js w DirectAdmin/cPanel, nvm).
    NPM_BIN="${NPM_BIN:-}"
    if [[ -z "$NPM_BIN" ]] && command -v npm >/dev/null 2>&1; then
        NPM_BIN="$(command -v npm)"
    fi
    if [[ -z "$NPM_BIN" ]]; then
        for candidate in \
            "$HOME"/nodevenv/*/*/bin/npm \
            "$HOME"/.nodevenv/*/*/bin/npm \
            "$HOME"/.nvm/versions/node/*/bin/npm \
            /usr/local/bin/npm \
            /usr/bin/npm \
            /opt/*/bin/npm; do
            if [[ -x "$candidate" ]]; then NPM_BIN="$candidate"; break; fi
        done
    fi

    if [[ -z "$NPM_BIN" ]] || ! "$NPM_BIN" --version >/dev/null 2>&1; then
        echo "⚠ Nie znaleziono działającego npm — pomijam budowanie front-endu." >&2
        echo "  Ustaw NPM_BIN=/ścieżka/do/npm i uruchom ponownie lub zbuduj ręcznie:" >&2
        echo "    cd \"$DEST\" && npm install && npm run build" >&2
    else
        # npm potrzebuje swojego node w PATH — dołóż jego katalog na początek.
        export PATH="$(dirname "$NPM_BIN"):$PATH"
        echo "→ npm: $NPM_BIN (v$("$NPM_BIN" --version 2>/dev/null || echo '?'))"

        "$NPM_BIN" install --no-audit --no-fund --ignore-scripts
        echo "✔ Zainstalowano zależności npm."

        "$NPM_BIN" run build
        echo "✔ Zbudowano zasoby front-end (Vite)."
    fi
fi

# --- 3. (Opcjonalnie) zamiana pełnych URL-i ze starą domeną w bazie SQLite ----
DB="database/database.sqlite"
if [[ "$REPLACE_DB_URLS" -eq 1 ]]; then
    if command -v sqlite3 >/dev/null && [[ -f "$DB" ]]; then
        cp "$DB" "${DB}.bak_$(date +%F_%H%M%S)"
        sqlite3 "$DB" "
            UPDATE pages SET
                about_team = REPLACE(COALESCE(about_team,''), '$OLD_DOMAIN', '$NEW_DOMAIN'),
                hub_hero   = REPLACE(COALESCE(hub_hero,''),   '$OLD_DOMAIN', '$NEW_DOMAIN')
            WHERE about_team LIKE '%$OLD_DOMAIN%' OR hub_hero LIKE '%$OLD_DOMAIN%';
        "
        echo "✔ Zamieniono URL-e ze starą domeną w bazie (kopia: ${DB}.bak_*)."
        echo "  Uwaga: to zamiana best-effort na znanych polach — sprawdz obrazy na stronie."
    else
        echo "⚠ Pominięto zamianę URL-i: brak sqlite3 lub pliku $DB." >&2
    fi
fi

echo "=============================================================="
echo " Gotowe. Sprawdź stronę: https://$NEW_DOMAIN"
echo "=============================================================="
