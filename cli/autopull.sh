#!/usr/bin/env bash
#
# autopull.sh — automatyczne pobieranie kodu po pushu na produkcji (feer.org.pl na h68).
#
# Uruchamiany z crona co minutę:
#   * * * * * /home/srv100843/domains/feer.org.pl/feer-web/cli/autopull.sh
#
# Gdy origin/main ma nowe commity: `git pull --ff-only`, a potem `artisan optimize` (cache konfiguracji, tras,
# zdarzeń i widoków), żeby nowe trasy działały od razu (bez tego stary cache tras dawał 500 „Route not defined”).
# Bez trybu serwisowego, bez Composera, npm i migracji — gdy pobrane commity ich wymagają (migracje, composer.lock,
# package*.json, vite.config.js, resources/js|css), w logu jest ostrzeżenie: wtedy trzeba uruchomić ./deploy.sh.
#
# Pomija przebieg, gdy: trwa deploy.sh (tryb serwisowy albo działający proces), poprzedni przebieg jeszcze trwa,
# wyjęta jest inna gałąź niż AUTOPULL_BRANCH, albo lokalne commity rozeszły się z origin (wtedy ręczny deploy).
# Log: storage/logs/autopull.log (tylko przebiegi z pobraniem, ostrzeżeniem albo błędem; przycinany do 2000 linii).
#
# Zmienne środowiskowe (opcjonalne):
#   PHP_BIN          binarka PHP CLI   (domyślnie /opt/alt/php84/usr/bin/php, a bez niej php84 / php)
#   AUTOPULL_BRANCH  gałąź             (domyślnie main)
#   AUTOPULL_REMOTE  zdalne repozytorium (domyślnie origin)
#
set -uo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT" || exit 1

BRANCH="${AUTOPULL_BRANCH:-main}"
REMOTE="${AUTOPULL_REMOTE:-origin}"
LOG="$ROOT/storage/logs/autopull.log"
LOCK="$ROOT/storage/framework/autopull.lock"
mkdir -p "$ROOT/storage/logs" "$ROOT/storage/framework"

if [ -z "${PHP_BIN:-}" ]; then
    if [ -x /opt/alt/php84/usr/bin/php ]; then PHP_BIN=/opt/alt/php84/usr/bin/php
    elif command -v php84 >/dev/null 2>&1; then PHP_BIN=php84
    else PHP_BIN=php; fi
fi

log() { printf '[%s] %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$*" >> "$LOG"; }

# Jeden przebieg naraz (flock jest na serwerze; lokalnie, np. na macOS, bez blokady).
if command -v flock >/dev/null 2>&1; then
    exec 9>"$LOCK"
    flock -n 9 || exit 0
fi

# Trwa wdrożenie: deploy.sh włącza tryb serwisowy i sam pobiera kod.
[ -f "$ROOT/storage/framework/down" ] && exit 0
if command -v pgrep >/dev/null 2>&1 && pgrep -u "$(id -u)" -f "[d]eploy\.sh" >/dev/null 2>&1; then
    exit 0
fi

current="$(git rev-parse --abbrev-ref HEAD 2>/dev/null)"
[ "$current" = "$BRANCH" ] || exit 0

TIMEOUT=""
command -v timeout >/dev/null 2>&1 && TIMEOUT="timeout 90"
if ! $TIMEOUT git fetch -q "$REMOTE" "$BRANCH" 2>>"$LOG"; then
    log "git fetch $REMOTE $BRANCH nie powiódł się"
    exit 1
fi

local_sha="$(git rev-parse HEAD)"
remote_sha="$(git rev-parse FETCH_HEAD)"
[ "$local_sha" = "$remote_sha" ] && exit 0

if ! git merge-base --is-ancestor "$local_sha" "$remote_sha"; then
    # Lokalnie są commity, których nie ma na origin (albo origin przepisano) — nic nie zmieniamy.
    git merge-base --is-ancestor "$remote_sha" "$local_sha" && exit 0
    log "pomijam: ${local_sha:0:8} i $REMOTE/$BRANCH ${remote_sha:0:8} rozeszły się — potrzebny ręczny ./deploy.sh"
    exit 1
fi

changed="$(git diff --name-only "$local_sha" "$remote_sha")"
if ! git pull -q --ff-only "$REMOTE" "$BRANCH" >>"$LOG" 2>&1; then
    log "git pull --ff-only nie powiódł się (lokalne zmiany w pobieranych plikach?) — potrzebny ręczny ./deploy.sh"
    exit 1
fi
log "pobrano ${local_sha:0:8}..${remote_sha:0:8}: $(git log --format=%s -1 "$remote_sha" | cut -c1-120) (plików: $(printf '%s\n' "$changed" | grep -c .))"

# Cache konfiguracji, tras, zdarzeń i widoków od nowa — bez trybu serwisowego.
if ! "$PHP_BIN" artisan optimize >>"$LOG" 2>&1; then
    log "UWAGA: artisan optimize nie powiódł się — sprawdź stronę i uruchom ./deploy.sh"
fi

needs="$(printf '%s\n' "$changed" | grep -E '^(database/migrations/|composer\.(json|lock)$|package(-lock)?\.json$|vite\.config\.js$|resources/(js|css)/)' | head -8 | tr '\n' ' ')"
if [ -n "$needs" ]; then
    log "UWAGA: pobrane zmiany wymagają ./deploy.sh (migracje, zależności albo assety): $needs"
fi

# Log nie rośnie bez końca.
if [ "$(wc -l < "$LOG" 2>/dev/null || echo 0)" -gt 2000 ]; then
    tail -n 1500 "$LOG" > "$LOG.tmp" && mv "$LOG.tmp" "$LOG"
fi

exit 0
