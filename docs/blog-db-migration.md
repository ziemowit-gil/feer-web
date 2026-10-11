# Baza bloga „Wiem FEER” — wyprowadzka z gita

Blog ma własne połączenie `blog` (SQLite). Historycznie plik leżał w
`database/blog.sqlite` i **jest śledzony w gicie**, a na produkcji zawiera żywe
dane (artykuły, komentarze, autorzy). Każdy commit ze zmianą tego pliku grozi
nadpisaniem danych albo zablokowaniem `git pull` na serwerze.

Docelowa lokalizacja: **`storage/app/blog/blog.sqlite`** (katalog `storage/`
nie jest nadpisywany przy wdrożeniu, a `storage/app/.gitignore` ignoruje go).

Przenosimy w dwóch krokach, w dwóch osobnych wdrożeniach.

## Jak aplikacja wybiera plik

`config/database.php` → `App\Support\BlogDatabasePath::resolve()`:

1. `BLOG_DB_DATABASE` z `.env`, jeśli niepuste,
2. `storage/app/blog/blog.sqlite`, jeśli plik istnieje,
3. `database/blog.sqlite` (stara lokalizacja).

Dopóki kopia nie powstanie, nic się nie zmienia. Przy cache konfiguracji
(`artisan optimize` / `config:cache`) wybór jest „zamrażany” — po przeniesieniu
trzeba odświeżyć cache (komenda i deploy.sh robią to same).

## Krok 1 — kopia do nowej lokalizacji (ten commit)

`deploy.sh` (krok 5) po `composer` i przed migracjami, w trybie serwisowym,
uruchamia `artisan blog:przenies-baze --force`, jeśli nowego pliku jeszcze nie
ma. Komenda:

- sprawdza `PRAGMA integrity_check` źródła, robi checkpoint WAL,
- zapisuje kopię zapasową źródła w `storage/app/blog/backups/blog-RRRRMMDD-GGMMSS.sqlite`,
- kopiuje bazę (`VACUUM INTO`, w razie braku — zwykła kopia) do pliku tymczasowego,
- porównuje liczbę wierszy w każdej tabeli i `integrity_check` kopii,
- dopiero wtedy atomowo zmienia nazwę na `storage/app/blog/blog.sqlite`.

Przy jakiejkolwiek rozbieżności nowy plik nie powstaje, a aplikacja dalej używa
starego. **Stary plik nigdy nie jest usuwany ani zmieniany.**

> Uwaga: wdrożenie, które *przynosi* ten commit, wykonuje jeszcze **stara**
> wersja deploy.sh (bash czyta skrypt, który był na dysku przed `git pull`).
> Ona nie zna kroku przenoszenia bazy ani nowych weryfikacji. Dlatego za
> pierwszym razem uruchom `./deploy.sh` **dwa razy** (za drugim razem odpowiedz
> „t” na pytanie „Czy mimo to kontynuować deploy?”) albo przenieś bazę ręcznie.
> Od tej wersji deploy.sh sam uruchamia swoją nową wersję, gdy `git pull` ją
> zmieni.

### Na serwerze (pierwsze wdrożenie tego commitu)

```bash
cd ~/ścieżka/do/feer-web
./deploy.sh            # 1. raz: stara wersja skryptu — pull, composer, build, migracje
./deploy.sh            # 2. raz: nowa wersja — krok 5 przenosi bazę bloga, pełne weryfikacje
php84 artisan system:sprawdz
php84 artisan tinker --execute="echo config('database.connections.blog.database');"
ls -l storage/app/blog/ storage/app/blog/backups/
```

Wariant ręczny zamiast drugiego `./deploy.sh`:

```bash
php84 artisan down --retry=30
php84 artisan blog:przenies-baze --force       # kopia zapasowa + kopia + weryfikacja wierszy
php84 artisan optimize:clear && php84 artisan optimize
php84 artisan up
php84 artisan system:sprawdz
```

Sprawdź na stronie bloga, że artykuły i komentarze są na miejscu.

### Po weryfikacji — zabezpieczenie starego pliku

Od teraz aplikacja nie używa `database/blog.sqlite`, ale plik jest nadal
śledzony. Żeby lokalne różnice nigdy nie blokowały `git pull` i nie były
zgłaszane przez `git status`:

```bash
git update-index --skip-worktree database/blog.sqlite
```

(Cofnięcie: `git update-index --no-skip-worktree database/blog.sqlite`.)

### Ochrona przed przypadkowym commitem (każdy klon, także lokalnie)

```bash
git config core.hooksPath .githooks
```

Hook `.githooks/pre-commit` blokuje dodanie/zmianę `database/*.sqlite` i
`storage/app/blog/*` w commicie (usunięcie z indeksu jest dozwolone). **Nigdy
nie commitujemy zmian `database/blog.sqlite`** — dopóki plik się w repo nie
zmienia, `git pull` na produkcji działa niezależnie od lokalnej zawartości.

## Krok 2 — usunięcie pliku z gita (osobny, późniejszy commit)

Dopiero gdy na produkcji `system:sprawdz` pokazuje nową lokalizację.
Commit `git rm --cached` usuwa plik **z dysku** każdego klonu przy `git pull`
(albo blokuje pull, jeśli plik jest zmieniony lokalnie). Aplikacja już go nie
używa, ale zachowujemy go jako archiwum.

### Commit (lokalnie)

```bash
git rm --cached database/blog.sqlite
printf '/database/blog.sqlite\n/database/blog.sqlite-*\n' >> .gitignore
git add .gitignore
git commit -m "chore(blog): baza bloga poza repozytorium"
```

### Na serwerze — przed wdrożeniem tego commitu

```bash
php84 artisan system:sprawdz                   # musi pokazać storage/app/blog/blog.sqlite
cp database/blog.sqlite storage/app/blog/backups/blog-legacy-$(date +%Y%m%d).sqlite
git update-index --no-skip-worktree database/blog.sqlite
git checkout -- database/blog.sqlite           # stara kopia jest już w backups/
./deploy.sh                                    # pull usuwa plik z repo; deploy.sh i tak go
                                               # przywraca z /tmp jako plik nieśledzony
git status --short database/                   # nic — plik jest w .gitignore
```

## Lokalnie

```bash
php artisan blog:przenies-baze --force
php artisan system:sprawdz
```

Testy używają bazy bloga w pamięci (`tests/Feature/BlogTestCase.php`), więc
lokalizacja pliku ich nie dotyczy.

## Ryzyka i odpowiedzi

- **Zapis do bloga w trakcie kopiowania** — komenda bez `--force` odmawia poza
  trybem serwisowym; deploy.sh kopiuje w trybie serwisowym.
- **Cache konfiguracji ze starą ścieżką** — komenda wykonuje `config:cache`,
  jeśli konfiguracja była zcache'owana; deploy.sh i tak robi `optimize`.
- **Ktoś ustawi `BLOG_DB_DATABASE`** — ma pierwszeństwo; komenda wtedy nic nie
  robi.
- **Powrót** — usuń (lub zmień nazwę) `storage/app/blog/blog.sqlite` i
  `php84 artisan optimize` — aplikacja wróci do `database/blog.sqlite` (bez
  zmian zapisanych w międzyczasie w nowym pliku).
