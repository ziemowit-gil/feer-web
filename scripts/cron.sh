#!/usr/bin/env bash
#
# Wpisy crontaba dla feer-web: dokładnie jeden działający harmonogram Laravela
# (schedule:run co minutę). Inne wpisy w crontabie (inne domeny, backupy) zostają
# nietknięte. Skrypt można uruchamiać wiele razy — nie dubluje wpisów.
#
# Usuwa wpisy feer-web, które nie działają albo dublują harmonogram, np.
#   * * * * * php84 -q …/artisan schedule:run …   (php84 to alias basha — w cronie go nie ma)
#
# Użycie:
#   scripts/cron.sh              instaluje / naprawia wpis
#   scripts/cron.sh --sprawdz    tylko diagnoza (kod 0 = w porządku, 1 = wymaga naprawy)
#   scripts/cron.sh --dry-run    pokazuje różnicę bez zapisu
#   scripts/cron.sh --usun       usuwa wpisy feer-web (np. przed przeniesieniem serwisu)
#
# Zmienne (opcjonalne):
#   PHP_BIN   binarka PHP CLI (domyślnie: /opt/alt/php84/usr/bin/php, potem php85, potem php z PATH)
#
# Kopia poprzedniego crontaba: storage/app/cron-backups/crontab-<data>.txt

set -euo pipefail

MODE="install"
case "${1:-}" in
    --sprawdz) MODE="check" ;;
    --dry-run) MODE="dry" ;;
    --usun)    MODE="remove" ;;
    -h|--help) sed -n '2,20p' "$0"; exit 0 ;;
    "") ;;
    *) echo "Nieznana opcja: $1 (zob. --help)" >&2; exit 2 ;;
esac

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd -P)"

if [ -z "${PHP_BIN:-}" ]; then
    for candidate in /opt/alt/php84/usr/bin/php /opt/alt/php85/usr/bin/php; do
        if [ -x "$candidate" ]; then PHP_BIN="$candidate"; break; fi
    done
    PHP_BIN="${PHP_BIN:-$(command -v php || true)}"
fi
if [ -z "$PHP_BIN" ] || [ ! -x "$PHP_BIN" ]; then
    echo "✗ Nie znaleziono PHP CLI (ustaw PHP_BIN=/pełna/ścieżka/php)." >&2
    exit 1
fi

LINE="* * * * * cd $APP_DIR && $PHP_BIN artisan schedule:run >> /dev/null 2>&1"

CURRENT="$(crontab -l 2>/dev/null || true)"

# Wpis feer-web = linia (nie komentarz) z katalogiem aplikacji i schedule:run.
is_ours() {
    case "$1" in
        \#*) return 1 ;;
    esac
    [[ "$1" == *"$APP_DIR"* && "$1" == *"schedule:run"* ]]
}

OURS=()
OTHERS=()
while IFS= read -r line || [ -n "$line" ]; do
    if is_ours "$line"; then OURS+=("$line"); else OTHERS+=("$line"); fi
done <<< "$CURRENT"

# Diagnoza.
problems=0
if [ ${#OURS[@]} -eq 0 ]; then
    echo "✗ Brak wpisu harmonogramu feer-web — zadania (kolejka, newsletter, synchronizacje) się nie wykonują."
    problems=1
fi
for line in "${OURS[@]+"${OURS[@]}"}"; do
    if [ "$line" = "$LINE" ]; then
        echo "✓ Poprawny wpis: $line"
        continue
    fi
    # Pierwsze słowo polecenia po 5 polach czasu (z pominięciem „cd … &&”).
    cmd="$(echo "$line" | awk '{ for (i = 6; i <= NF; i++) { if ($i == "cd") { i += 2; continue } if ($i == "&&") continue; print $i; exit } }')"
    if [[ "$cmd" != /* ]]; then
        echo "✗ Wpis nie działa w cronie (\"$cmd\" bez pełnej ścieżki, np. alias basha): $line"
    else
        echo "! Wpis inny niż wzorcowy: $line"
    fi
    problems=1
done
if [ ${#OURS[@]} -gt 1 ]; then
    echo "✗ Wpisów harmonogramu jest ${#OURS[@]} — działające uruchamiają zadania kilka razy."
    problems=1
fi

if [ "$MODE" = "check" ]; then
    [ $problems -eq 0 ] && echo "✓ Crontab w porządku." || echo "→ Napraw: scripts/cron.sh"
    exit $problems
fi

# Nowa wersja: wszystkie inne wpisy bez zmian + (o ile nie --usun) jeden wzorcowy wpis.
NEW="$(printf '%s\n' "${OTHERS[@]+"${OTHERS[@]}"}")"
# Usuń końcowe puste linie, żeby nie narastały przy każdym uruchomieniu.
NEW="$(printf '%s' "$NEW" | sed -e :a -e '/^\n*$/{$d;N;ba' -e '}')"
if [ "$MODE" != "remove" ]; then
    NEW="${NEW:+$NEW
}$LINE"
fi

if [ "$NEW" = "$CURRENT" ]; then
    echo "✓ Bez zmian — crontab jest już w porządku."
    exit 0
fi

if [ "$MODE" = "dry" ]; then
    echo "Różnica (bez zapisu):"
    diff <(printf '%s\n' "$CURRENT") <(printf '%s\n' "$NEW") || true
    exit 0
fi

BACKUP_DIR="$APP_DIR/storage/app/cron-backups"
mkdir -p "$BACKUP_DIR"
BACKUP="$BACKUP_DIR/crontab-$(date +%Y%m%d-%H%M%S).txt"
printf '%s\n' "$CURRENT" > "$BACKUP"

printf '%s\n' "$NEW" | crontab -
echo "✓ Crontab zapisany. Kopia poprzedniej wersji: ${BACKUP#$APP_DIR/}"
if [ "$MODE" = "remove" ]; then
    echo "  Usunięto wpisy feer-web."
else
    echo "  Wpis: $LINE"
fi
