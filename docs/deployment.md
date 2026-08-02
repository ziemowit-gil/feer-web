# Dokumentacja wdrożeniowa — FEER CMS

**Autor:** Ziemowit Gil <ziemowit.gil@feer.org.pl>

---

## Wymagania

| Komponent | Wersja minimalna |
|-----------|-----------------|
| PHP       | 8.3 (produkcja: `php85`) |
| Laravel   | 13.8 |
| Node.js   | 20 (npm) |
| Composer  | 2.x |
| Baza danych | SQLite (dwie bazy: główna i blog) |

---

## Struktura baz danych

Projekt używa **dwóch oddzielnych baz SQLite**:

| Baza | Plik | Połączenie | Migracje |
|------|------|------------|----------|
| Główna | `database/database.sqlite` | `sqlite` (domyślne) | `database/migrations/` |
| Blog Wiem FEER | `database/blog.sqlite` | `blog` | `database/migrations/blog/` |

> **Uwaga:** `php artisan migrate` bez dodatkowych parametrów migruje tylko główną bazę.
> Bazę bloga migruj zawsze z flagą `--database=blog --path=database/migrations/blog`.

---

## Zmienne środowiskowe (`.env`)

Skopiuj `.env.example` jako `.env` i uzupełnij wymagane wartości:

```dotenv
# ─── Aplikacja ────────────────────────────────────────────────────────────────
APP_NAME=FEER              # Nazwa wyświetlana w panelu i e-mailach
APP_ENV=production         # local | production
APP_KEY=                   # php artisan key:generate
APP_DEBUG=false            # NIGDY true na produkcji
APP_URL=https://feer.org.pl

# Adres URL panelu (domyślnie /admin; zmień aby ukryć)
# ADMIN_PREFIX=zarzadzanie

# ─── Bazy danych ──────────────────────────────────────────────────────────────
DB_CONNECTION=sqlite
BLOG_DB_DATABASE=/ścieżka/do/database/blog.sqlite   # bezwzględna ścieżka

# ─── Poczta ───────────────────────────────────────────────────────────────────
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=noreply@feer.org.pl
MAIL_PASSWORD=hasło
MAIL_FROM_ADDRESS=noreply@feer.org.pl
MAIL_FROM_NAME="FEER"

# ─── Microsoft 365 SSO (panel i strefa współpracownika) ───────────────────────
# Rejestracja w Azure Portal → Microsoft Entra ID → Rejestracje aplikacji
# Redirect URI (produkcja): https://feer.org.pl/auth/microsoft/callback
MICROSOFT_CLIENT_ID=
MICROSOFT_CLIENT_SECRET=
MICROSOFT_REDIRECT_URI=https://feer.org.pl/auth/microsoft/callback
MICROSOFT_TENANT_ID=common   # common = dowolne MS; lub konkretne ID tenanta

# ─── Unsplash (wyszukiwarka zdjęć w bibliotece mediów) ────────────────────────
# Bezpłatny klucz na unsplash.com/developers (plan Demo: 50 req/h)
UNSPLASH_ACCESS_KEY=

# ─── Cache i sesje ────────────────────────────────────────────────────────────
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

---

## Pierwsze uruchomienie (lokalne)

```bash
# 1. Zależności PHP
composer install

# 2. Plik .env
cp .env.example .env
php artisan key:generate

# 3. Utwórz bazy SQLite
touch database/database.sqlite
touch database/blog.sqlite

# 4. Migracje
php artisan migrate
php artisan migrate --database=blog --path=database/migrations/blog

# 5. Dane przykładowe (opcjonalnie)
php artisan db:seed

# 6. Zależności JS i build
npm install
npm run dev   # lub: npm run build

# 7. Serwer deweloperski
php artisan serve
```

> Link do panelu: `http://localhost:8000/{ADMIN_PREFIX}` (domyślnie `/admin`)

---

## Wdrożenie produkcyjne — skrypt `deploy.sh`

Projekt zawiera gotowy skrypt wdrożeniowy `deploy.sh` realizujący 7-krokowy proces:

```
[1/7]  Tryb serwisowy — php85 artisan down
[2/7]  Kopia zapasowa obu baz SQLite do /tmp
[3/7]  git pull origin main (z przywróceniem baz po git checkout)
[4/7]  composer update --no-dev --optimize-autoloader
[5/7]  npm ci && npm run build
[6/7]  Migracje: baza główna, baza bloga
[7/7]  php85 artisan optimize:clear && optimize
       php85 artisan up
```

### Uruchomienie

```bash
./deploy.sh
```

Skrypt pyta o potwierdzenie przed wdrożeniem i pokazuje listę nadchodzących commitów.

### Zmienne środowiskowe skryptu (opcjonalne nadpisanie)

| Zmienna | Domyślna | Opis |
|---------|----------|------|
| `PHP_BIN` | `php85` / `php84` / `php83` (autodetect) | Binarka PHP CLI |
| `COMPOSER_BIN` | `composer` | Binarka Composer |
| `NPM_BIN` | `/opt/alt/alt-nodejs20/root/usr/bin/npm` | Binarka npm |
| `BRANCH` | `main` | Gałąź Git do wdrożenia |

Przykład lokalnego testu:

```bash
PHP_BIN=php COMPOSER_BIN=composer NPM_BIN=npm BRANCH=develop ./deploy.sh
```

### Obsługa błędów

Skrypt ma ustawiony `trap ERR` — w razie błędu automatycznie wyłącza tryb serwisowy (`artisan up`). Jeśli krok Composera się nie powiedzie (np. lock file nie pasuje do wersji PHP), skrypt kontynuuje i wyświetla instruckje do ręcznego uruchomienia.

---

## Migracje ręczne

```bash
# Baza główna
php85 artisan migrate --force

# Baza bloga
php85 artisan migrate --force \
    --database=blog \
    --path=database/migrations/blog
```

> Na serwerze produkcyjnym zawsze `php85`, nigdy `php`.

---

## Cache i optymalizacja

```bash
# Wyczyszczenie wszystkich cache
php85 artisan optimize:clear

# Zbudowanie cache (config, routes, views)
php85 artisan optimize
```

---

## Microsoft 365 SSO — konfiguracja Azure

1. Przejdź do **Azure Portal → Microsoft Entra ID → Rejestracje aplikacji → Nowa rejestracja**.
2. Ustaw **Redirect URI** (Web):
   - Panel: `https://twoja-domena.pl/auth/microsoft/callback`
   - Strefa współpracownika: `https://twoja-domena.pl/auth/member/microsoft/callback`
3. Utwórz **Secret** w zakładce „Certyfikaty i wpisy tajne".
4. Skopiuj **Client ID** i **Tenant ID** do `.env`.

Aby ograniczyć logowanie wyłącznie do użytkowników z domeny organizacji, ustaw `MICROSOFT_TENANT_ID` na konkretny ID tenanta (zamiast `common`).

---

## Prawa dostępu do katalogów

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

---

## Kolejka zadań (Queue)

Projekt używa kolejki bazodanowej (`QUEUE_CONNECTION=database`). Uruchom worker:

```bash
php85 artisan queue:work --tries=3 --timeout=60
```

Na produkcji zalecane jest uruchomienie przez Supervisor lub cron:

```bash
# /etc/cron.d/feer-queue
* * * * * www-data php85 /ścieżka/do/artisan queue:work --stop-when-empty
```

---

## Zaplanowane zadania (Scheduler)

Dodaj do crontab serwera:

```bash
* * * * * www-data php85 /ścieżka/do/artisan schedule:run >> /dev/null 2>&1
```

---

## Przywracanie z kopii zapasowej

Skrypt `deploy.sh` tworzy kopie baz w `/tmp/feer-db-YYYYMMDD-HHMMSS.sqlite`
i `/tmp/feer-blog-db-YYYYMMDD-HHMMSS.sqlite`. Przywrócenie:

```bash
cp /tmp/feer-db-20260101-120000.sqlite database/database.sqlite
cp /tmp/feer-blog-db-20260101-120000.sqlite database/blog.sqlite
php85 artisan optimize:clear
```

---

## Struktura katalogów

```
feer-web/
├── app/
│   ├── Http/Controllers/        # Kontrolery (Admin/, Auth/, publiczne)
│   ├── Models/                  # Modele Eloquent
│   ├── Services/                # Serwisy (TwoFactor, Webinar, ...)
│   └── Mail/                    # Klasy e-mail
├── database/
│   ├── database.sqlite          # Główna baza danych (git-ignored)
│   ├── blog.sqlite              # Baza bloga (git-ignored)
│   ├── migrations/              # Migracje głównej bazy
│   └── migrations/blog/         # Migracje bazy bloga
├── resources/
│   ├── views/                   # Szablony Blade
│   └── js/ / css/               # Źródła front-endowe (Vite)
├── public/                      # Web root — wskazuje tu Apache/Nginx
├── storage/
│   └── app/public/              # Pliki mediów (link przez storage:link)
├── docs/                        # Dokumentacja projektu
│   ├── controllers.md           # Spis kontrolerów
│   └── deployment.md            # Ten dokument
└── deploy.sh                    # Skrypt wdrożeniowy
```

---

## Symlink storage (po każdym fresh install)

```bash
php85 artisan storage:link
```
