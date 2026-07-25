# Plan wdrożenia — 5 zadań (FEER-web)

Dokument roboczy. Każde zadanie: **interpretacja → zmiany w bazie (migracja) → model → backend → panel admina → front publiczny → uwagi/decyzje**.

Konwencje potwierdzone w kodzie, których się trzymamy:
- Kolor marki to zmienne CSS `--color-brand` / `--color-brand-dark` / `--color-brand-light`, nadpisywane per-request w `resources/views/layouts/site.blade.php:26`. Utility `text-brand`, `bg-brand`, `border-brand`, `bg-brand-light` idą za nimi automatycznie.
- Ustawienia globalne = singleton `App\Models\SiteSetting` (`current()`, cache, cast `encrypted` dla sekretów). Panel: jeden monolityczny `resources/views/admin/settings/edit.blade.php` z zakładkami **Alpine** (`x-data="{ tab: '...' }"`).
- Konfiguracja runtime (wzorzec Microsoft SSO): wartości z DB wstrzykiwane przez `config([...])` — patrz `AppServiceProvider::boot()` i `MicrosoftAuthController::applyMicrosoftConfig()`.
- Formularz strony (`admin/pages/form.blade.php`) używa **vanilla JS + `data-*` + `classList.toggle('hidden', …)`** (NIE Alpine); repeater harmonogramu: `<template data-schedule-template>` z placeholderem `__INDEX__`.
- Pola formularzy admina: surowy HTML+Tailwind. Idiomy: input `w-full rounded border-gray-300 focus:border-brand focus:ring-brand`, label `mb-1 block text-sm font-bold`, help `mt-1 text-xs text-muted`, błąd `mt-1 text-sm text-red-600`.
- Przełącznik bool z gwarantowanym `0`: `<input type="hidden" name="x" value="0">` + `<input type="checkbox" name="x" value="1">`, odczyt `$request->boolean('x')`.
- Dostępność (WCAG) domyślnie: alt-y, nagłówki semantyczne, `Color::button()` / `contrastSafeColor()` pilnują kontrastu 4.5:1.

---

## Zadanie 1 — Harmonogram jako CTA w projekcie

### Interpretacja
Mechanizm już **w 80% istnieje**: stronę typu `schedule` (harmonogram) można przypiąć do projektu (`pages.project_id` + `pages.project_display` ∈ {`link`,`tab`,`inline`}), a w prawym `<aside>` projektu harmonogram już renderuje się jako przycisk-CTA (`projects/show.blade.php:232`). Brakuje **osadzenia samej tabeli harmonogramu w treści projektu** — obecnie tryb `inline`/`tab` renderuje tylko `title` + `content` strony, bez tabeli (`projects/show.blade.php:118` i `:131`).

**Podejście (rekomendowane):** nie dodajemy harmonogramu jako osobnych kolumn w `projects` — reużywamy istniejący mechanizm stron przypiętych do projektu. Wystarczy:
1. wyodrębnić render harmonogramu do reużywalnego partiala,
2. w projekcie renderować ten partial dla przypiętych stron-harmonogramów (inline i w zakładce),
3. dołożyć wyraźną sekcję-CTA „Harmonogram” w treści projektu.

**Brak migracji.** (Alternatywa — kolumny `schedule_items` bezpośrednio w `projects` — odrzucona: duplikuje logikę i panel; opisana tylko jako opcja.)

### Krok 1: wyodrębnienie partiala
Nowy plik `resources/views/partials/schedule.blade.php` — przenosimy tu render z `page/show.blade.php:111-182` (badge + `schedule_pending` / `schedule_change_notice` + tabela). Partial przyjmuje `$page` oraz opcjonalne `$showHeading` (żeby w projekcie nie dublować `<h1>`):

```blade
@php
    $scheduleItems = collect($page->schedule_items ?? [])
        ->filter(fn ($i) => ! empty($i['date']) || ! empty($i['time']) || ! empty($i['location']) || ! empty($i['note']));
    $formatDate = function ($value) {
        if (blank($value)) return null;
        try { return \Illuminate\Support\Carbon::parse($value)->translatedFormat('d.m.Y'); }
        catch (\Throwable $e) { return $value; }
    };
    $showHeading = $showHeading ?? true;
@endphp

@if ($page->schedule_pending)
    <div class="mt-6 flex items-start gap-3 rounded-lg border border-amber-300 bg-amber-50 p-5" role="note" aria-label="Status harmonogramu">
        <i class="fa-solid fa-clock mt-0.5 text-amber-700" aria-hidden="true"></i>
        <div>
            <p class="font-bold text-amber-900">Harmonogram jeszcze nie został opublikowany</p>
            <p class="text-sm text-amber-900">Pracujemy nad ustaleniem terminów — zapraszamy wkrótce.</p>
        </div>
    </div>
@else
    @if ($page->schedule_change_notice)
        <div class="mt-6 flex items-start gap-3 rounded-lg border border-amber-300 bg-amber-50 p-4" role="note" aria-label="Informacja o zmianie w harmonogramie">
            <i class="fa-solid fa-triangle-exclamation mt-0.5 text-amber-700" aria-hidden="true"></i>
            <div>
                <p class="font-bold text-amber-900">Zmiana w harmonogramie</p>
                <p class="text-sm text-amber-900">{!! nl2br(e($page->schedule_change_notice)) !!}</p>
            </div>
        </div>
    @endif

    @if ($scheduleItems->isNotEmpty())
        @if ($showHeading)
            <h2 id="harmonogram-{{ $page->id }}" class="mb-4 mt-8 flex items-center gap-2 text-xl font-bold text-ink">
                <i class="fa-solid fa-calendar-days text-brand" aria-hidden="true"></i> Harmonogram
            </h2>
        @endif
        <div class="overflow-x-auto rounded-xl border border-gray-200">
            <table class="w-full text-left text-sm" aria-labelledby="harmonogram-{{ $page->id }}">
                <caption class="sr-only">Harmonogram — data, godzina, miejsce i uwagi. Terminy oznaczone „zmienione” uległy zmianie.</caption>
                <thead class="bg-gray-50 text-xs font-bold uppercase tracking-wide text-muted">
                    <tr>
                        <th scope="col" class="px-4 py-3">Data</th>
                        <th scope="col" class="px-4 py-3">Godzina</th>
                        <th scope="col" class="px-4 py-3">Miejsce</th>
                        <th scope="col" class="px-4 py-3">Uwagi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($scheduleItems as $item)
                        <tr class="{{ ! empty($item['changed']) ? 'bg-amber-50' : '' }}">
                            <th scope="row" class="whitespace-nowrap px-4 py-3 text-left font-medium text-ink">
                                {{ $formatDate($item['date'] ?? null) ?? '—' }}
                                @if (! empty($item['changed']))
                                    <span class="ml-1 inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-900">
                                        <i class="fa-solid fa-rotate" aria-hidden="true"></i> Zmienione
                                    </span>
                                @endif
                            </th>
                            <td class="whitespace-nowrap px-4 py-3 text-ink">{{ $item['time'] ?? '' ?: '—' }}</td>
                            <td class="px-4 py-3 text-ink">{{ $item['location'] ?? '' ?: '—' }}</td>
                            <td class="px-4 py-3 text-muted">{{ $item['note'] ?? '' ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endif
```

W `page/show.blade.php` gałąź `@elseif ($page->isSchedule())` zastępujemy include’em partiala (zachowując `<h1>` i `$menuSiblings`), żeby był jeden render.

### Krok 2: osadzenie w projekcie
`ProjectController@show` — dociągamy strony wraz z ich mediami/relacjami (dziś: `load('category','publishedPages')`; wystarcza, bo pola harmonogramu są na `pages`).

`resources/views/projects/show.blade.php`:
- **inline** (`:118`): dla stron `isSchedule()` renderujemy partial zamiast `content`:
  ```blade
  @foreach ($inlinePages as $subpage)
      <div class="mt-8">
          <h2 class="mb-3 text-xl font-bold text-ink">{{ $subpage->title }}</h2>
          @if ($subpage->isSchedule())
              @include('partials.schedule', ['page' => $subpage, 'showHeading' => false])
          @elseif ($subpage->content)
              <div class="prose max-w-none text-ink">{!! $subpage->content !!}</div>
          @endif
          <a href="{{ route('page.show', $subpage) }}" class="mt-3 inline-flex items-center gap-2 text-sm font-bold text-brand hover:text-brand-dark">
              Otwórz jako osobną stronę <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
          </a>
      </div>
  @endforeach
  ```
- **tab** (`:131`): analogicznie w panelu zakładki — `isSchedule()` → include partiala.
- **CTA (nowość):** tuż pod nagłówkiem projektu (po `:39`) wyróżniony blok, jeśli projekt ma przypiętą stronę-harmonogram. Pokazuje najbliższy termin + przycisk (kotwica do sekcji inline albo link do osobnej strony):
  ```blade
  @php $schedulePage = $project->publishedPages->firstWhere(fn ($p) => $p->isSchedule()); @endphp
  @if ($schedulePage)
      <div class="mb-8 flex flex-col gap-3 rounded-xl border border-brand/20 bg-brand-light p-5 sm:flex-row sm:items-center sm:justify-between">
          <div class="flex items-start gap-3">
              <i class="fa-solid fa-calendar-days mt-0.5 text-xl text-brand" aria-hidden="true"></i>
              <div>
                  <p class="font-bold text-ink">Harmonogram zajęć i spotkań</p>
                  <p class="text-sm text-muted">Sprawdź terminy w ramach tego projektu.</p>
              </div>
          </div>
          <a href="{{ $schedulePage->project_display === 'inline' ? '#harmonogram-'.$schedulePage->id : route('page.show', $schedulePage) }}"
             class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand px-4 py-2.5 font-bold text-white transition hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
              Zobacz harmonogram <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
          </a>
      </div>
  @endif
  ```

### Uwagi
- Zero zmian w DB, panelu edycji strony ani w walidacji — reużywamy istniejącą stronę typu „Harmonogram” przypiętą do projektu (`project_display = inline` daje osadzenie tabeli, `tab` — zakładkę, `link` — sam przycisk w aside, jak dziś).
- Instrukcja dla redaktora (do docs/panelu): utwórz stronę typu *Harmonogram zajęć / spotkań*, w zakładce „Publikacja i powiązania” wskaż projekt i tryb wyświetlania.

---

## Zadanie 2 — Widoczność koordynatora (per projekt + na kontakcie)

### Interpretacja
Koordynator to pola tekstowe na projekcie (`coordinator_name/email/phone`, `is_featured_contact`) — **nie** użytkownik panelu. Chcemy:
- **per projekt**: przełącznik „pokazuj koordynatora”,
- **globalnie (domyślnie)**: główny wyłącznik dla całego serwisu.

**Decyzja (patrz sekcja „Decyzje”):** globalny wyłącznik w `SiteSetting` (spójne z resztą ustawień). Detaliczna specyfikacja mówiła o „tabeli kont użytkowników” — to nie pasuje do modelu danych (koordynator ≠ user); rekomenduję `SiteSetting`.

Logika widoczności: koordynator widoczny ⟺ `SiteSetting.show_coordinators` (master) **oraz** `project.show_coordinator`.

### Migracja
`database/migrations/2026_07_26_100000_add_coordinator_visibility.php`:
```php
public function up(): void
{
    Schema::table('projects', function (Blueprint $table) {
        $table->boolean('show_coordinator')->default(true)->after('is_featured_contact');
    });
    Schema::table('site_settings', function (Blueprint $table) {
        $table->boolean('show_coordinators')->default(true);
    });
}
public function down(): void
{
    Schema::table('projects', fn (Blueprint $t) => $t->dropColumn('show_coordinator'));
    Schema::table('site_settings', fn (Blueprint $t) => $t->dropColumn('show_coordinators'));
}
```

### Modele
- `Project`: dodać `show_coordinator` do `$fillable`, cast `boolean`; helper:
  ```php
  public function showsCoordinator(): bool
  {
      return $this->show_coordinator && SiteSetting::current()->show_coordinators;
  }
  ```
- `SiteSetting`: dodać `show_coordinators` do `$fillable` i cast `boolean`.

### Backend
- `Admin\ProjectController::validated()`: `$data['show_coordinator'] = $request->boolean('show_coordinator');`
- `Admin\SiteSettingController::update()`: dodać `'show_coordinators'` do reguł (`boolean`) i do bloku `$request->boolean(...)`.
- `ContactController::index()`: gdy globalnie wyłączone — nie pokazujemy sekcji; per-projekt filtrujemy:
  ```php
  $projects = (SiteSetting::current()->isModuleEnabled('projects') && SiteSetting::current()->show_coordinators)
      ? Project::where('is_published', true)->where('is_completed', false)
          ->where('show_coordinator', true)
          ->with('category')->orderByDesc('is_featured_contact')->orderBy('title')->get()
      : collect();
  ```

### Panel admina
- `admin/projects/form.blade.php` (obok pól koordynatora) — przełącznik domyślnie włączony:
  ```blade
  <label class="flex items-start gap-2 text-sm">
      <input type="hidden" name="show_coordinator" value="0">
      <input type="checkbox" name="show_coordinator" value="1"
          @checked(old('show_coordinator', $project->show_coordinator ?? true))
          class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
      <span>
          <span class="font-bold">Pokazuj koordynatora</span>
          <span class="block text-xs text-muted">Dane kontaktowe koordynatora będą widoczne na stronie projektu i na stronie „Kontakt”.</span>
      </span>
  </label>
  ```
- `admin/settings/edit.blade.php`, zakładka **Kontakt** — globalny wyłącznik `show_coordinators` (ten sam idiom hidden+checkbox).

### Front publiczny
- `projects/show.blade.php:249` — blok „Kontakt w sprawie projektu” obejmujemy dodatkowo `showsCoordinator()`:
  ```blade
  @if (! $project->is_completed && $project->showsCoordinator())
      … (istniejący blok)
  @endif
  ```
- `contact/show.blade.php:120` — bez zmian w bladzie (sterujemy zapytaniem w kontrolerze); gdy lista pusta, sekcja się nie renderuje (już jest `@if ($projects->isNotEmpty())`).

### Uwagi
- `show_coordinator` domyślnie `true` → istniejące projekty zachowują obecne zachowanie.
- Jeśli wolisz, żeby przełącznik ukrywał tylko imię/telefon, a zostawiał ogólny e-mail — powiedz; wtedy zawężam warunek tylko do fragmentu bloku.

---

## Zadanie 3 — Konfiguracja poczty: SMTP lub Microsoft Azure (Graph)

### Interpretacja
Panel administracyjny z wyborem trybu bramki pocztowej:
- **`smtp`** — host / port / użytkownik / hasło / szyfrowanie (tls/ssl/brak),
- **`graph`** — Microsoft 365 przez Microsoft Graph API (OAuth2 client-credentials, app-only), z możliwością reużycia poświadczeń z sekcji „Logowanie MS365”,
- fallback: brak konfiguracji → dziedziczenie z `.env` (jak dziś).

Konfiguracja przechowywana w `SiteSetting` (hasło/sekret zaszyfrowane), wstrzykiwana runtime w `AppServiceProvider::boot()` — analogicznie do Microsoft SSO.

> ⚠️ **Graph wymaga** rejestracji w Azure z uprawnieniem **`Mail.Send` (Application)** + zgodą administratora tenantu; wiadomości wychodzą „jako” konkretna skrzynka (UPN/adres nadawcy musi istnieć w tenancie). SMTP Office365 (basic auth) bywa wyłączony przez politykę Microsoftu — dlatego Graph jest wariantem docelowym dla MS365.

### Migracja
`database/migrations/2026_07_26_110000_add_mail_config_to_site_settings.php`:
```php
public function up(): void
{
    Schema::table('site_settings', function (Blueprint $table) {
        $table->string('mail_transport')->default('default');   // default | smtp | graph
        $table->string('mail_from_address')->nullable();
        $table->string('mail_from_name')->nullable();
        // SMTP
        $table->string('mail_host')->nullable();
        $table->unsignedSmallInteger('mail_port')->nullable();
        $table->string('mail_username')->nullable();
        $table->text('mail_password')->nullable();              // encrypted
        $table->string('mail_encryption')->nullable();          // tls | ssl | null
        // Microsoft Graph
        $table->boolean('mail_graph_use_sso_credentials')->default(true);
        $table->string('mail_graph_client_id')->nullable();
        $table->text('mail_graph_client_secret')->nullable();   // encrypted
        $table->string('mail_graph_tenant_id')->nullable();
        $table->string('mail_graph_from')->nullable();          // skrzynka nadawcza (UPN)
    });
}
```

### Model `SiteSetting`
- `$fillable`: wszystkie powyższe.
- `$casts`: `mail_password => 'encrypted'`, `mail_graph_client_secret => 'encrypted'`, `mail_graph_use_sso_credentials => 'boolean'`, `mail_port => 'integer'`.
- Metody pomocnicze:
  ```php
  public function mailConfigured(): bool
  {
      return in_array($this->mail_transport, ['smtp', 'graph'], true);
  }

  /** Poświadczenia Graph: własne albo (opcjonalnie) współdzielone z logowaniem MS365. */
  public function mailGraphConfig(): array
  {
      if ($this->mail_graph_use_sso_credentials) {
          $ms = $this->microsoftConfig();
          return [
              'client_id' => $this->mail_graph_client_id ?: $ms['client_id'],
              'client_secret' => $this->mail_graph_client_secret ?: $ms['client_secret'],
              'tenant' => $this->mail_graph_tenant_id ?: $ms['tenant'],
              'from' => $this->mail_graph_from ?: $this->mail_from_address,
          ];
      }
      return [
          'client_id' => $this->mail_graph_client_id,
          'client_secret' => $this->mail_graph_client_secret,
          'tenant' => $this->mail_graph_tenant_id ?: 'common',
          'from' => $this->mail_graph_from ?: $this->mail_from_address,
      ];
  }
  ```

### Custom transport dla Graph
`app/Mail/Transport/MicrosoftGraphTransport.php` — własny transport Symfony Mailer:
```php
<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;

class MicrosoftGraphTransport extends AbstractTransport
{
    public function __construct(
        private string $clientId,
        private string $clientSecret,
        private string $tenant,
        private string $from,
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $token = $this->accessToken();

        $sender = $this->from ?: ($email->getFrom()[0] ?? null)?->getAddress();

        $payload = [
            'message' => [
                'subject' => $email->getSubject(),
                'body' => [
                    'contentType' => $email->getHtmlBody() ? 'HTML' : 'Text',
                    'content' => $email->getHtmlBody() ?? $email->getTextBody() ?? '',
                ],
                'toRecipients' => $this->addresses($email->getTo()),
                'ccRecipients' => $this->addresses($email->getCc()),
                'bccRecipients' => $this->addresses($email->getBcc()),
                'replyTo' => $this->addresses($email->getReplyTo()),
            ],
            'saveToSentItems' => true,
        ];

        $response = Http::withToken($token)
            ->post("https://graph.microsoft.com/v1.0/users/".urlencode($sender)."/sendMail", $payload);

        if ($response->failed()) {
            throw new \RuntimeException('Microsoft Graph sendMail: '.$response->status().' '.$response->body());
        }
    }

    private function accessToken(): string
    {
        $response = Http::asForm()->post(
            "https://login.microsoftonline.com/{$this->tenant}/oauth2/v2.0/token",
            [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'scope' => 'https://graph.microsoft.com/.default',
                'grant_type' => 'client_credentials',
            ]
        );
        if ($response->failed()) {
            throw new \RuntimeException('Microsoft Graph token: '.$response->status().' '.$response->body());
        }
        return $response->json('access_token');
    }

    private function addresses(array $addresses): array
    {
        return array_map(fn (Address $a) => [
            'emailAddress' => ['address' => $a->getAddress(), 'name' => $a->getName()],
        ], $addresses);
    }

    public function __toString(): string
    {
        return 'microsoft-graph';
    }
}
```

### Rejestracja transportu + runtime config
W `AppServiceProvider::boot()` (na końcu):
```php
Mail::extend('microsoft-graph', function () {
    $cfg = SiteSetting::current()->mailGraphConfig();
    return new \App\Mail\Transport\MicrosoftGraphTransport(
        (string) $cfg['client_id'], (string) $cfg['client_secret'],
        (string) $cfg['tenant'], (string) $cfg['from'],
    );
});

$settings = SiteSetting::current();
if ($settings->mailConfigured()) {
    if ($settings->mail_from_address) {
        config(['mail.from.address' => $settings->mail_from_address]);
    }
    if ($settings->mail_from_name) {
        config(['mail.from.name' => $settings->mail_from_name]);
    }

    if ($settings->mail_transport === 'smtp') {
        config(['mail.default' => 'smtp', 'mail.mailers.smtp' => array_merge(
            config('mail.mailers.smtp'),
            array_filter([
                'host' => $settings->mail_host,
                'port' => $settings->mail_port,
                'username' => $settings->mail_username,
                'password' => $settings->mail_password,
                'scheme' => $settings->mail_encryption === 'ssl' ? 'smtps' : null,
            ], fn ($v) => ! is_null($v)),
        )]);
    } elseif ($settings->mail_transport === 'graph') {
        config([
            'mail.default' => 'microsoft-graph',
            'mail.mailers.microsoft-graph' => ['transport' => 'microsoft-graph'],
        ]);
    }
}
```
> Uwaga wydajnościowa: `SiteSetting::current()` jest cache’owany w request. `Mail::extend` rejestruje tylko fabrykę (lazy) — poświadczenia czyta dopiero przy wysyłce.

### Panel admina
- `admin/settings/edit.blade.php`: nowa zakładka `mail` (dodać wpis w tablicy `$tabs`, blok `x-show="tab === 'mail'"`), wzorowana na sekcji MS365 (lokalny `x-data`):
  - select `mail_transport`: *Dziedzicz z serwera (.env)* / *SMTP* / *Microsoft 365 (Graph)*,
  - `mail_from_address`, `mail_from_name` (wspólne),
  - blok SMTP (`x-show="transport === 'smtp'"`): host, port, username, `password` (type=password, placeholder „zapisane — zostaw puste”), encryption (select tls/ssl/brak),
  - blok Graph (`x-show="transport === 'graph'"`): checkbox „Użyj poświadczeń z logowania MS365”, a gdy odznaczone: client_id / client_secret / tenant_id; zawsze `mail_graph_from` (skrzynka nadawcza) + instrukcja o uprawnieniu `Mail.Send`,
  - przycisk **„Wyślij testową wiadomość”** (POST na nową trasę `admin.ustawienia.mail-test`) → wysyła próbny mail na `contact_email` i pokazuje wynik/wyjątek.
- `Admin\SiteSettingController::update()`: reguły walidacji dla nowych pól + zachowanie sekretów (jeśli `mail_password`/`mail_graph_client_secret` puste → `unset`, jak przy `microsoft_client_secret`). Nowa metoda `mailTest(Request $request)`.
- Sidebar: bez zmian (nowa zakładka w istniejącym ekranie „Ustawienia strony”).

### Uwagi/decyzje
- **Zakres teraz:** SMTP jest prosty i bezpieczny; Graph to dodatkowy transport + zależność od konfiguracji Azure. Patrz pytanie w „Decyzje”.
- Wysyłka maili idzie przez kolejkę (`QUEUE_CONNECTION=database`, `ContactMessageMail` używa `Queueable`) — runtime-config w `AppServiceProvider` działa też w workerze kolejki (provider bootuje się w każdym procesie). ✅
- Alternatywa dla Graph bez własnego transportu: paczka `microsoft/microsoft-graph` + wrapper — ale własny lekki transport (powyżej) nie dokłada zależności i wystarcza do `sendMail`.

---

## Zadanie 4 — Nowy typ strony „O organizacji”

### Interpretacja
Nowy typ `about` w `Page::TYPES`: **motto** (duży cytat) + **galeria kilku zdjęć w ładnym układzie** + dodatkowe „fajne fitery”. Fitery są otwarte — patrz „Decyzje” (proponuję: liczby/statystyki, oś czasu, wartości/kafelki). Dodanie typu dotyka 5 miejsc (`TYPES`, DB+model, walidacja z zerowaniem pól, formularz admina, gałąź render publiczny).

### Migracja
`database/migrations/2026_07_26_120000_add_about_fields_to_pages.php`:
```php
Schema::table('pages', function (Blueprint $table) {
    $table->text('about_motto')->nullable()->after('schedule_pending');
    $table->string('about_motto_author')->nullable()->after('about_motto');
    $table->json('about_gallery')->nullable()->after('about_motto_author'); // [{media_id|url, alt}]
    $table->json('about_stats')->nullable()->after('about_gallery');         // [{value, label}]
    $table->json('about_timeline')->nullable()->after('about_stats');        // [{year, text}]
});
```

### Model `Page`
- `TYPES`: dodać `'about' => 'O organizacji'`.
- `$fillable`: `about_motto, about_motto_author, about_gallery, about_stats, about_timeline`.
- `$casts`: `about_gallery/about_stats/about_timeline => 'array'`.
- Helper `public function isAbout(): bool { return $this->type === 'about'; }`.

### Galeria — mechanizm zdjęć (rekomendacja)
Najczystszy istniejący wzorzec: **osobny model obrazów `morphMany`** wzorowany na `Attachment` (Spatie `singleFile('image')`, `caption`, `order`), edytowany po zapisaniu strony (jak zakładka „Pliki”). Zalety: kolejność, alt/caption, kadrowanie przez media library.

Nowy model `PageImage` (lub generyczny `GalleryItem` morphable):
```php
class PageImage extends Model implements HasMedia {
    use InteractsWithMedia;
    protected $fillable = ['page_id','caption','alt','order'];
    public function registerMediaCollections(): void { $this->addMediaCollection('image')->singleFile(); }
    protected function imageUrl(): Attribute { return Attribute::make(get: fn () => $this->getFirstMediaUrl('image') ?: null); }
}
```
+ migracja `page_images` (page_id FK cascade, caption, alt, order), relacja `Page::images(): HasMany`, kontroler `Admin\PageImageController` (store/destroy/updateOrder) + trasy `podstrony/{page}/zdjecia`, oraz partial-uploader w formularzu (widoczny gdy `$page->exists`, jak „Pliki”).

> **Lżejsza alternatywa (bez nowej tabeli):** `about_gallery` jako JSON adresów z media library, wybieranych repeaterem podpiętym pod istniejący picker (`admin.multimedia.images`). Mniej kodu backendu, ale trzeba dorobić JS spinający picker z repeaterem. Rekomenduję `PageImage` — spójne z resztą i porządniejsze.

### Backend `Admin\PageController::validated()`
Dodać reguły:
```php
'about_motto' => ['nullable','string','max:1000'],
'about_motto_author' => ['nullable','string','max:255'],
'about_stats' => ['nullable','array'],
'about_stats.*.value' => ['nullable','string','max:50'],
'about_stats.*.label' => ['nullable','string','max:120'],
'about_timeline' => ['nullable','array'],
'about_timeline.*.year' => ['nullable','string','max:20'],
'about_timeline.*.text' => ['nullable','string','max:500'],
```
oraz — analogicznie do bloku `event` — zerowanie pól, gdy `type !== 'about'` (czyszczenie stat/timeline z pustych wierszy jak w harmonogramie).

### Formularz admina `admin/pages/form.blade.php`
- W panelu „Typ i układ” dodać grupę `<div data-about-fields class="… {{ $currentType === 'about' ? '' : 'hidden' }}">` z: motto (textarea), autor motta, repeatery stat i timeline (idiom `<template>` + `__INDEX__` jak harmonogram).
- Rozszerzyć toggle JS (linie ~388) o linię:
  ```js
  const aboutFields = document.querySelector('[data-about-fields]');
  // w handlerze change:
  if (aboutFields) aboutFields.classList.toggle('hidden', typeSelect.value !== 'about');
  ```
- Zdjęcia: zakładka/sekcja uploadera `PageImage` widoczna gdy `$page->exists` (jak „Pliki do pobrania”).

### Front publiczny `page/show.blade.php`
Nowa gałąź `@elseif ($page->isAbout())` przed `@else` (standard). Proponowany układ (WCAG: `<figure>`/`<figcaption>`, alt-y, nagłówki):
- **Motto** — duży `<blockquote>` wyśrodkowany, w kolorze marki (`text-brand`), z autorem.
- **Treść** `{!! $page->content !!}` w `.prose`.
- **Galeria** — responsywny grid „mozaikowy” (`grid grid-cols-2 md:grid-cols-3 gap-3`, pierwszy obraz `col-span-2 row-span-2`), `loading="lazy"`, `<figcaption>` z podpisem.
- **Statystyki** — rząd liczb (`grid grid-cols-2 md:grid-cols-4`), duża wartość `text-brand` + etykieta.
- **Oś czasu** — pionowa lista z linią i kropkami w kolorze marki.

### Uwagi
- Slug „o-organizacji” nie koliduje z `RESERVED_SLUGS`.
- „Inne fajne fitery” doprecyzujemy — patrz „Decyzje” (statystyki/oś czasu/zespół/wartości). Kod repeaterów jest wspólny, więc dołożenie kolejnego jest tanie.

---

## Zadanie 5 — Dynamiczna kolorystyka wg grupy docelowej (Projekt / News)

### Interpretacja
Przy tworzeniu/edycji **projektu** i **newsa** wybór grupy docelowej:
- `brand` — zwykły „Kolor marki” (domyślny),
- `ngo` — dedykowany, konfigurowalny w panelu **kolor dla NGO**.

Na stronie danego projektu/newsa (i opcjonalnie na kaflach list) elementy interfejsu przełączają się na wybrany kolor. Dzięki temu, że kolor marki to zmienne CSS nadpisywane w `layouts/site.blade.php`, wystarczy **per-strona nadpisać `--color-brand*`** kolorem NGO — cała paleta idzie za tym automatycznie.

### Migracja
`database/migrations/2026_07_26_130000_add_audience_and_ngo_color.php`:
```php
Schema::table('projects', fn (Blueprint $t) => $t->string('audience')->default('brand')->after('for_whom'));
Schema::table('news', fn (Blueprint $t) => $t->string('audience')->default('brand')->after('excerpt'));
Schema::table('site_settings', fn (Blueprint $t) => $t->string('ngo_color')->nullable());
```

### Modele
- Wspólna stała (np. w `SiteSetting` albo osobny enum):
  ```php
  public const AUDIENCES = ['brand' => 'Kolor marki (domyślny)', 'ngo' => 'NGO (dedykowany kolor)'];
  ```
- `Project` i `News`: `audience` do `$fillable`.
- `SiteSetting`: `ngo_color` do `$fillable`; paleta na podstawie dowolnego koloru (reużywamy prywatny `shade()` przez nowe publiczne metody):
  ```php
  /** [brand, dark, light] dla wskazanego koloru; null → kolor marki. */
  public function brandPalette(?string $hex = null): array
  {
      $base = ($hex && \App\Support\Color::isValid($hex)) ? $hex : $this->brand_color;
      return ['color' => $base, 'dark' => $this->shade($base, -0.25), 'light' => $this->shade($base, 0.92)];
  }

  /** Efektywny kolor dla grupy docelowej. */
  public function audienceColor(string $audience): string
  {
      return $audience === 'ngo' && \App\Support\Color::isValid($this->ngo_color)
          ? $this->ngo_color : $this->brand_color;
  }
  ```
  (`shade()` zmienić z `private` na `protected`/`public` — używane tylko wewnątrz modelu, wystarczy `public`.)

### Layout — per-strona override
`resources/views/layouts/site.blade.php:26` — zamiast sztywnego brand_color liczymy paletę z opcjonalnej zmiennej `$brandColor` (przekazywanej tylko przez widoki projektu/newsa; w Blade zmienne widoku-dziecka są dostępne w layoucie, który rozszerza):
```blade
@php $palette = $siteSettings->brandPalette($brandColor ?? null); @endphp
<style>
    :root {
        --color-brand: {{ $palette['color'] }};
        --color-brand-dark: {{ $palette['dark'] }};
        --color-brand-light: {{ $palette['light'] }};
    }
</style>
```

### Kontrolery publiczne
- `ProjectController@show`: `$brandColor = SiteSetting::current()->audienceColor($project->audience);` → `view('projects.show', compact('project','brandColor'))`.
- `NewsController@show`: analogicznie dla `$news->audience`.
- (Opcjonalnie) listy: na kaflach projektu/newsa można lokalnie ustawić kolor per-kafel (inline `style` + `Color::button`), jeśli chcesz rozróżnienie już na listach.

### Panel admina
- `admin/projects/form.blade.php` i `admin/news/form.blade.php`: select `audience` z `SiteSetting::AUDIENCES` (idiom select jak wyżej).
- `admin/settings/edit.blade.php` (zakładka **Ogólne**, obok `brand_color`): color picker + hex input dla `ngo_color`, z podglądem kontrastu (istnieje już skrypt live-preview WCAG — dopiąć drugi wskaźnik).
- Walidacja:
  - `Admin\ProjectController` / `Admin\NewsController`: `'audience' => ['nullable', Rule::in(array_keys(SiteSetting::AUDIENCES))]`, domyślnie `'brand'`.
  - `Admin\SiteSettingController`: `'ngo_color' => ['nullable','regex:/^#[0-9a-fA-F]{6}$/']`, i (jak `brand_color`) przepuścić przez `contrastSafeColor()` przy zapisie, żeby jako `text-brand` na białym miał kontrast ≥ 4.5:1.

### Uwagi
- Ponieważ `ngo_color` przechodzi przez `contrastSafeColor()`, teksty/przyciski w kolorze NGO zachowają WCAG AA (spójne z traktowaniem `brand_color`).
- Rozwiązanie jest rozszerzalne: dołożenie kolejnej grupy = wpis w `AUDIENCES` + kolumna koloru w `SiteSetting` + gałąź w `audienceColor()`.

---

## Podsumowanie zmian w bazie (migracje)
| # | Tabela | Kolumny |
|---|--------|---------|
| 1 | — | brak (reużycie stron przypiętych do projektu) |
| 2 | `projects`, `site_settings` | `show_coordinator` (bool, dflt true); `show_coordinators` (bool, dflt true) |
| 3 | `site_settings` | `mail_transport`, `mail_from_address/name`, `mail_host/port/username/password(enc)/encryption`, `mail_graph_*` |
| 4 | `pages` (+ nowa `page_images`) | `about_motto`, `about_motto_author`, `about_gallery/stats/timeline` (json); `page_images` |
| 5 | `projects`, `news`, `site_settings` | `audience` (×2, dflt 'brand'); `ngo_color` |

Wszystkie migracje z metodą `down()`. Uruchomienie: `php artisan migrate`.
> Pamiętać: blog „Wiem FEER” ma osobne połączenie SQLite (`blog`) — te migracje go nie dotyczą.

## Kolejność implementacji (proponowana)
1. **Zad. 1** (bez DB, szybki efekt, refaktor partiala).
2. **Zad. 2** (mała migracja, prosta logika).
3. **Zad. 5** (mała migracja, elegancki override palety).
4. **Zad. 4** (nowy typ + galeria — najwięcej UI).
5. **Zad. 3** (poczta — osobno, wymaga danych Azure/SMTP do testów).

## Decyzje do potwierdzenia
1. **Poczta (zad. 3):** budujemy teraz oba tryby (SMTP + Graph) czy najpierw SMTP, a Graph w drugim kroku? Czy macie rejestrację Azure z uprawnieniem `Mail.Send` (Application) i adres skrzynki nadawczej?
2. **Globalny wyłącznik koordynatora (zad. 2):** `SiteSetting` (rekomendacja) czy jednak pole w tabeli użytkowników wg pierwotnego zapisu?
3. **„Fajne fitery” dla „O organizacji” (zad. 4):** które sekcje poza motto+galerią — statystyki (liczby), oś czasu, wartości/kafelki, zespół?
4. **Grupa docelowa (zad. 5):** tylko dwie grupy (marka / NGO) czy przewidujemy więcej? Czy kolorystyka ma zmieniać się też na kaflach list, czy tylko na stronie szczegółów?
