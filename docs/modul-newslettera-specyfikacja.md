# Moduł Newslettera — koncepcja i specyfikacja techniczno-funkcjonalna

Dokument projektowy dla FEER-web (Laravel 13, PHP 8.3, multisite). Opisuje **rozbudowę** istniejących elementów (tabela `subscribers`, double opt-in w `SubscribeController`, `MailTemplate`, transport Microsoft Graph, `SpamGuard`, `PushSubscription`) do pełnego modułu kampanii newsletterowych z edytorem Mosaico, kolejkowaną wysyłką wielokanałową i analityką.

Wersja: 1.1 · Data: 2026-10-09 · Autor: Ziemowit Gil

> **Status wdrożenia (2026-10-09):** moduł jest zaimplementowany w `modules/Newsletter/` (iteracje I1–I3 oraz kanały push/SMS, digest, segmenty, SZO/CRM z I4). Sekcja 6 opisuje, co dokładnie powstało i jak to uruchomić.

---

## 0. Założenia i decyzje projektowe

| # | Decyzja | Uzasadnienie |
|---|---------|--------------|
| D1 | Moduł w `modules/Newsletter/` (wzorzec `modules/Sklep`), włączany w panelu „Moduły”. | Spójność z istniejącą architekturą modułową; możliwość wyłączenia bez usuwania danych. |
| D2 | Tabele z prefiksem `newsletter_*`. Istniejąca tabela `campaigns` **pozostaje** tabelą kampanii zbiórkowych (`App\Models\Campaign`). | Uniknięcie kolizji nazw; kampania e-mail ≠ kampania zbiórkowa. |
| D3 | Istniejąca tabela `subscribers` zostaje rozszerzona (nie zastępowana). | Zachowanie dotychczasowych subskrybentów, tokenów i tematów. |
| D4 | Edytor wiadomości: **Mosaico** (voidlabs/mosaico, GPL-3.0) z własnym szablonem `feer-1` w barwach i czcionce brandbooku. | Dojrzały, responsywny edytor drag-and-drop, generuje HTML tabelowy zgodny z klientami poczty; działa po stronie przeglądarki, backend tylko do obrazków. |
| D5 | Wysyłka **wielokanałowa** przez sterowniki kanałów: `email` (domyślny i obowiązkowy), `webpush` (istniejący `minishlink/web-push`), `sms` (opcjonalny, SMSAPI/Twilio). | Treść kampanii ma wariant pełny (HTML) i skrócony (tytuł + tekst + link), więc ten sam obiekt może wyjść różnymi kanałami. |
| D6 | Kolejka: `QUEUE_CONNECTION=database` (już skonfigurowana) + Laravel Bus Batches; limit tempa przez middleware `RateLimited`. | Zero nowej infrastruktury; Redis opcjonalnie przyspiesza limiter. |
| D7 | Ochrona formularza: istniejący `SpamGuard` (honeypot + żeton czasowy + zadanie tekstowe). **Bez obrazkowej CAPTCHY** (WCAG 1.1.1). Cloudflare Turnstile tylko jako opcja włączana w ustawieniach. | FEER obsługuje osoby z niepełnosprawnościami; reCAPTCHA jest barierą. |
| D8 | Tagi personalizacji w składni `{{nazwa}}` (jak w `MailTemplate`), a nie `{nazwa}`. | Jeden interpolator w całym systemie; mniejsze ryzyko kolizji z CSS w HTML-u. |
| D9 | Dane osobowe: pseudonimizacja IP (HMAC), ślepy indeks e-maila, szyfrowanie sekretów dostawców castem `encrypted` w `SiteSetting`. | Wzorzec już stosowany (`cleantalk_access_key`, `szo_token`). |
| D10 | Każdy rekord listy/kampanii ma `site_id` (multisite). | Ośrodki federacji mogą prowadzić własne newslettery z tej samej instalacji. |
| D11 | Dedykowany, edytowalny w panelu **formularz zapisu** (`newsletter_forms`) renderowany komponentem `<x-newsletter-widget>`; strona systemowa `/newsletter` pokazuje formularz domyślny. | Treści, pola, tematy, klauzula i listy docelowe bez edycji kodu; stary kod osadzenia zewnętrznego zostaje tylko jako fallback. |
| D12 | Synchronizacja subskrybentów do **SZO** (istniejące API `POST /api/v1/forms.php`, zgoda `newsletter`) i do dowolnego **CRM** przez podpisany webhook JSON. | SZO jest CRM-em fundacji; webhook obsługuje HubSpot/Pipedrive/Zapier bez dedykowanych integracji. |
| D13 | Blok „Najnowsze aktualności” z sortowaniem **wg daty lub wg kategorii** (grupowanie z nagłówkami kategorii w kolorze kategorii). | Redaktor nie przepisuje aktualności; digest cykliczny wysyła się sam, gdy są nowości. |

---

## 1. Architektura i funkcjonalności

### 1.1 Mapa modułu

```
modules/Newsletter/
├── module.json
├── Providers/NewsletterServiceProvider.php     # trasy, widoki, konfiguracja, harmonogram
├── Channels/                                   # sterowniki kanałów (D5)
│   ├── ChannelDriver.php  (interfejs)
│   ├── EmailChannel.php
│   ├── WebPushChannel.php
│   └── SmsChannel.php
├── Jobs/
│   ├── DispatchCampaign.php                    # buduje partię (batch) z odbiorców
│   ├── SendDelivery.php                        # 1 odbiorca × 1 kanał
│   └── ProcessBounceWebhook.php
├── Http/Controllers/Admin/                     # dashboard, listy, segmenty, kampanie, raporty, ustawienia
├── Http/Controllers/Public/                    # widget, potwierdzenie, wypis, preferencje, tracking
├── Services/
│   ├── SegmentResolver.php                     # reguły JSON → query builder
│   ├── Personalizer.php                        # {{tagi}} → wartości
│   ├── TrackingRewriter.php                    # linki → /n/c/{uuid}/{hash}, piksel otwarcia
│   └── Mosaico/{ImageController, TemplateRegistry}.php
└── resources/views/{admin,public,mosaico}/
```

### 1.2 Podział funkcji: Back-office vs Frontend

| Obszar | Back-office (panel `/admin/newsletter/*`) | Frontend (publiczny) |
|---|---|---|
| **Subskrybenci** | lista z filtrami, karta subskrybenta (historia aktywności, zgody, kanały), ręczne dodanie, import CSV/JSON z mapowaniem kolumn i deduplikacją, eksport CSV/JSON, anonimizacja RODO, czarna lista | widget zapisu (strona główna, stopka, sidebar), strona `/newsletter`, potwierdzenie DOI, strona preferencji (tematy, kanały, częstotliwość), wypis jednym kliknięciem |
| **Listy i segmenty** | listy statyczne (ręczne), segmenty dynamiczne (reguły: tematy, status, aktywność, ośrodek, tagi, data zapisu), podgląd liczności „na żywo” | — |
| **Wiadomości** | kreator kampanii (5 kroków), Mosaico, biblioteka szablonów, wariant skrócony dla push/SMS, test A/B tematu, wysyłka testowa | wersja „zobacz w przeglądarce” `/n/w/{uuid}` |
| **Wysyłka** | planowanie (natychmiast / data / strefa czasu odbiorcy), throttling, wybór dostawcy per kanał, pauza/wznowienie/anulowanie, podgląd kolejki | — |
| **Analityka** | dashboard, raport kampanii (OR, CTR, bounce, wypisy, mapa cieplna kliknięć), porównanie kampanii, eksport raportu | piksel otwarć, przekierowania kliknięć (niewidoczne dla użytkownika) |
| **Zgodność** | rejestr zgód, wersjonowane treści klauzul (`GdprClause`), retencja i automatyczne czyszczenie, dziennik operacji (`activity_log`) | treść zgody przy formularzu, link do polityki prywatności, potwierdzenie wypisu |
| **Ustawienia** | dostawcy e-mail (Graph / SES / Mailgun / Postmark / SMTP), push (VAPID — już są), SMS, limity, domyślny nadawca, DKIM/SPF checklist, Turnstile | — |

### 1.3 Zarządzanie subskrybentami

**Statusy** (`subscribers.status`):

| Status | Znaczenie | Przejścia |
|---|---|---|
| `pending` | zapisany, nie kliknął linku potwierdzającego | → `confirmed` (klik), → `expired` (po 7 dniach, auto) |
| `confirmed` | aktywny (double opt-in zaliczone) | → `unsubscribed`, `bounced`, `complained`, `suppressed` |
| `unsubscribed` | sam się wypisał (link / preferencje / List-Unsubscribe) | → `pending` (ponowny zapis, nowe DOI) |
| `bounced` | twarde odbicie (adres nie istnieje) | tylko ręcznie admin → `pending` |
| `complained` | zgłoszenie spamu (FBL) | nigdy automatycznie |
| `suppressed` | czarna lista admina | ręcznie |
| `expired` | DOI niepotwierdzone w terminie | → `pending` (ponowny zapis) |
| `anonymized` | dane usunięte na żądanie (RODO), rekord zachowany do statystyk | końcowy |

**Segmentacja** — dwa typy:

- *Lista statyczna* (`newsletter_lists`, pivot `newsletter_list_subscriber`) — np. „Uczestnicy konferencji 2026”.
- *Segment dynamiczny* (`newsletter_segments.rules` JSON) — wyliczany w momencie wysyłki:

```json
{
  "match": "all",
  "rules": [
    { "field": "status",      "op": "eq",        "value": "confirmed" },
    { "field": "topics",      "op": "contains",  "value": ["events", "news"] },
    { "field": "site_id",     "op": "in",        "value": [1, 4] },
    { "field": "last_open_at","op": "within_days","value": 90 },
    { "field": "tags",        "op": "not_contains","value": ["pracownik"] },
    { "field": "channels",    "op": "contains",  "value": ["email"] }
  ]
}
```

`SegmentResolver` tłumaczy reguły na Eloquent (`whereJsonContains`, `where('last_open_at','>=',…)`), a panel pokazuje liczność segmentu (`count()` z cache 60 s).

**Import / eksport**

| Funkcja | Szczegóły |
|---|---|
| Import CSV/JSON | krok 1 upload (max 10 MB), krok 2 mapowanie kolumn (`email`, `name`, `phone`, `topics`, `tags`), krok 3 opcje: *status po imporcie* (`pending` → wyślemy DOI; `confirmed` → tylko gdy admin zaznaczy „posiadam udokumentowaną zgodę” + wpisze źródło i datę zgody, zapisywane do rejestru zgód), deduplikacja po `email_hash`, raport błędów do pobrania |
| Eksport CSV/JSON | zakres: filtr bieżący / lista / segment; kolumny wybieralne; eksport logowany w `activity_log` (kto, kiedy, ile rekordów) — wymóg rozliczalności RODO |
| Historia aktywności | oś czasu na karcie subskrybenta: zapis, DOI wysłane/potwierdzone, zmiany preferencji, każda dostawa (kanał, status), otwarcia, kliknięcia, odbicia, wypis, operacje admina |

### 1.4 Edytor wiadomości — Mosaico

**Dlaczego Mosaico:** otwarty (GPL-3.0, repozytorium `voidlabs/mosaico`), działa w całości w przeglądarce (Knockout.js), ma edytor drag-and-drop bloków, inline-edycję tekstu, panel stylów, podgląd mobile/desktop i generuje HTML tabelowy z wbudowanym inlinerem CSS. Backend jest potrzebny tylko do trzech rzeczy: upload obrazków, przeskalowanie/placeholder obrazków i zapis modelu.

**Integracja w panelu**

| Element | Realizacja |
|---|---|
| Osadzenie | Blade `admin/newsletter/campaigns/editor.blade.php` ładuje `mosaico.min.js` + `mosaico.min.css` (zbudowane z npm `mosaico`, publikowane do `public/vendor/mosaico/`), inicjalizacja `Mosaico.init({ imgProcessorBackend, emailProcessorBackend, fileuploadConfig })` |
| Zapis | `Mosaico.start()` → callback `viewModel.exportMetadata()`, `exportJSON()` (model treści) i `exportHTML()`; zapisujemy do `newsletter_campaigns.editor_metadata`, `editor_content`, `html_body`. Autosave co 60 s (PATCH JSON). |
| Backend obrazków | `POST /admin/newsletter/mosaico/upload` → Spatie Media Library (kolekcja `newsletter`), `GET /admin/newsletter/mosaico/img?src=&method=resize\|cover\|placeholder&params=W,H` → Intervention/GD (GD jest w PHP) z cache w `storage/app/public/newsletter/cache` |
| Szablony | `newsletter_templates.kind = mosaico` → ścieżka do `template.html` szablonu Mosaico; `kind = html` → surowy HTML (dla zaawansowanych i importu z zewnątrz); `kind = text` → sam tekst (push/SMS) |
| Podgląd | iframe `sandbox` z `html_body` po personalizacji dla wybranego subskrybenta testowego; przełącznik 360 / 600 / 100 % |

**Szablon `feer-1` — brandbook w Mosaico.** Szablon to zwykły plik HTML z atrybutami `data-ko-*` i blokiem definicji `@supports -ko-blockdefs`. Kolory i fonty brandbooku wpisujemy jako wartości domyślne właściwości stylowych, więc redaktor od razu pracuje w barwach FEER, ale może je zmienić w panelu stylów (z pilnowaniem kontrastu, patrz §1.4.1).

```html
<!-- resources/mosaico/templates/feer-1/template.html (fragment) -->
<style type="text/css">
@supports -ko-blockdefs {
  /* paleta marki — wartości domyślne widżetów koloru */
  color            { label: Kolor; widget: color }
  brandColor       { label: Kolor marki;        widget: color; default: #1E6DFF }
  brandDark        { label: Kolor marki (ciemny); widget: color; default: #1752BF }
  accentColor      { label: Kolor akcentu;      widget: color; default: #EA8F00 }
  textColor        { label: Kolor tekstu;       widget: color; default: #1D1D1A }
  bgColor          { label: Tło;                widget: color; default: #FFFFFF }
  face             { label: Krój pisma; widget: select;
                     options: Montserrat, Arial, Helvetica, sans-serif=Montserrat|
                              Arial, Helvetica, sans-serif=Arial|
                              Georgia, serif=Georgia }

  template preview { label: Szablon FEER }
  contentTheme     { label: Styl treści;  properties: face=Montserrat, Arial, Helvetica, sans-serif;
                     textColor=#1D1D1A; linkColor=#1752BF; bgColor=#FFFFFF }
  buttonStyle      { label: Przycisk; properties: face; color=#FFFFFF; buttonColor=#1752BF; radius=6px }

  /* bloki */
  heroBlock        { label: Nagłówek z obrazem; properties: image; text; buttonStyle }
  singleArticle    { label: Artykuł; properties: image; titleText; longText; buttonLink }
  twoColumns       { label: Dwie kolumny }
  eventTile        { label: Kafel wydarzenia; properties: date; titleText; place; buttonLink }
  donateCta        { label: Wezwanie do wsparcia; properties: accentColor; text; buttonLink }
  footerBlock      { label: Stopka z wypisem }
}
</style>

<!-- Montserrat: ładowana tam, gdzie klient poczty na to pozwala (Apple Mail, iOS, Thunderbird);
     Outlook/Gmail użyją Arial z fallbacku. -->
<!--[if !mso]><!-->
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700;800&display=swap" rel="stylesheet">
<!--<![endif]-->

<table data-ko-block="heroBlock" width="100%" style="background-color: #FFFFFF; -ko-background-color: @bgColor">
  <tr><td align="center" style="font-family: Montserrat, Arial, Helvetica, sans-serif; -ko-font-family: @face">
    <img data-ko-editable="image.src" width="600" alt="" data-ko-attr-alt="image.alt" />
    <h1 data-ko-editable="text" style="color: #1D1D1A; -ko-color: @textColor; font-size: 28px; font-weight: 800">
      Tytuł newslettera
    </h1>
    <a data-ko-editable="buttonLink.text" data-ko-attr-href="buttonLink.url"
       style="background-color: #1752BF; -ko-background-color: @buttonStyle.buttonColor;
              color: #FFFFFF; -ko-color: @buttonStyle.color; font-weight: 700; padding: 12px 24px;
              border-radius: 6px; -ko-border-radius: @buttonStyle.radius; display: inline-block">
      Czytaj więcej
    </a>
  </td></tr>
</table>

<table data-ko-block="footerBlock" width="100%" style="background-color: #1D1D1A">
  <tr><td style="color: #FFFFFF; font-family: Montserrat, Arial, sans-serif; font-size: 13px; padding: 24px">
    <span data-ko-editable="legalText">Fundacja Edukacji Ekonomicznej i Rozwoju FEER …</span><br>
    <a href="{{unsubscribe_url}}" style="color: #FFFFFF; text-decoration: underline">Wypisz się</a> ·
    <a href="{{preferences_url}}" style="color: #FFFFFF">Zmień preferencje</a> ·
    <a href="{{webversion_url}}" style="color: #FFFFFF">Zobacz w przeglądarce</a>
  </td></tr>
</table>
```

> Uwaga licencyjna: Mosaico jest na GPL-3.0. Używamy go jako biblioteki w aplikacji serwowanej przez WWW (bez dystrybucji binarnej), co nie wymusza zmiany licencji FEER-web. Własny szablon `feer-1` i modyfikacje Mosaico trzymamy w osobnym katalogu `resources/mosaico/` z plikiem `LICENSE` Mosaico.

#### 1.4.1 Pilnowanie kontrastu w edytorze

Panel stylów Mosaico pozwala wybrać dowolny kolor. Dodajemy walidator (hook `viewModel.contentModel` → `ko.computed`) który przy każdej zmianie koloru tekstu/tła liczy kontrast WCAG i wyświetla ostrzeżenie w pasku edytora: *„Kontrast 2,8:1 — poniżej 4,5:1, tekst może być nieczytelny”*. To rozszerzenie istniejącego `Color::button()` / `contrastSafeColor()` po stronie PHP. Jasny brand `#1E6DFF` na bieli ma 4,48:1, więc **na tekst i przyciski używamy `#1752BF` (5,8:1)**; `#1E6DFF` zostaje dla dużych pól tła z białym tekstem ≥ 18 px bold i elementów dekoracyjnych. Pomarańcz `#EA8F00` tylko jako tło z ciemnym tekstem `#1D1D1A` (6,7:1) lub akcent graficzny, nigdy jako kolor tekstu na bieli.

#### 1.4.2 Tagi personalizacji

| Tag | Wartość | Fallback |
|---|---|---|
| `{{first_name}}` | pierwsze słowo z `subscribers.name` | atrybut `\|default:Dzień dobry` → `{{first_name\|default:Drogi Czytelniku}}` |
| `{{name}}` | pełne imię/nick | pusty ciąg |
| `{{email}}` | adres | — |
| `{{unsubscribe_url}}` | `/n/u/{token}` (GET pokazuje potwierdzenie, POST wypisuje; nagłówek List-Unsubscribe prowadzi do one-click POST) | — |
| `{{preferences_url}}` | `/n/p/{token}` — tematy, kanały, częstotliwość | — |
| `{{webversion_url}}` | `/n/w/{delivery_uuid}` | — |
| `{{confirm_url}}` | tylko w mailu DOI | — |
| `{{site_name}}`, `{{site_url}}` | z `SiteSetting` (ośrodek) | — |
| `{{campaign_title}}`, `{{date:d.m.Y}}` | metadane | — |
| `{{topic_list}}` | lista tematów subskrybenta (`topicLabels()`) | — |

`Personalizer` używa tego samego mechanizmu co `MailTemplate::interpolate()`, rozszerzonego o filtry (`|default:`, `|upper`, `|date:`) i o **escapowanie HTML** wartości pochodzących od użytkownika (imię), żeby nie dało się wstrzyknąć HTML-u przez formularz zapisu.

### 1.5 Planowanie i wysyłka

#### 1.5.1 Kanały (D5)

```php
namespace Modules\Newsletter\Channels;

interface ChannelDriver
{
    public function key(): string;                                  // email | webpush | sms
    public function supports(Subscriber $s): bool;                  // ma e-mail / subskrypcję push / numer + zgodę
    public function render(Campaign $c, Subscriber $s): Message;    // pełny HTML lub wariant skrócony
    public function send(Message $m, Delivery $d): DeliveryResult;  // status, provider_message_id, błąd
    public function maxPerMinute(): int;                            // limit tempa dla tego kanału
}
```

| Kanał | Transport | Treść | Zgoda | Zwrotka |
|---|---|---|---|---|
| `email` (obowiązkowy) | Laravel Mail: `msgraph` (istniejący `MicrosoftGraphTransport`), `ses`, `mailgun`, `postmark`, `resend`, `smtp` — wybór w ustawieniach, z fallbackiem do `.env` | `html_body` + auto-generowany `text_body` (html2text) | DOI | webhooki bounce/complaint |
| `webpush` | `minishlink/web-push` + istniejące `push_subscriptions` (łączone z subskrybentem przez `subscribers.id` lub cookie `nl_sid`) | `short_title` (≤ 60 zn.), `short_text` (≤ 120 zn.), `short_url`, ikona | zgoda przeglądarki + checkbox w preferencjach | HTTP 404/410 → usuń subskrypcję |
| `sms` (opcjonalny) | SMSAPI (PL) lub Twilio przez `Notification` channel | `short_text` ≤ 160 zn. + skrócony link `/n/c/…` | osobna zgoda (`consents.channel = sms`), numer w E.164 | DLR webhook |

Kampania ma pole `channels` (JSON, np. `["email","webpush"]`). Dla każdego odbiorcy tworzymy jedną `newsletter_deliveries` na **każdy obsługiwany kanał**; subskrybent dostaje wiadomość tylko kanałami, na które wyraził zgodę w preferencjach.

#### 1.5.2 Cykl życia kampanii

```
draft ──► scheduled ──► queued ──► sending ──► sent
  ▲           │            │          │
  └── edit ◄──┘      cancel│    pause │ resume
                           ▼          ▼
                        cancelled   paused
```

| Krok | Mechanizm |
|---|---|
| Zaplanowanie | `scheduled_at` (UTC) + opcja „wyślij o 9:00 czasu lokalnego odbiorcy” (`subscribers.timezone`, domyślnie Europe/Warsaw) |
| Harmonogram | `schedule:run` co minutę → `newsletter:dispatch-due` → `DispatchCampaign` dla kampanii z `scheduled_at <= now()` |
| Budowa partii | `DispatchCampaign` rozwiązuje segment → chunk po 500 → `Bus::batch([SendDelivery…])` z `->allowFailures()`, `->name('campaign:'.$id)`; id partii w `newsletter_campaigns.batch_id` (pauza = `cancel()` partii + nowa partia dla pozostałych) |
| Throttling | job middleware `RateLimited('newsletter-email')` — limit z ustawień (np. 600/min dla SES, 30/min dla SMTP współdzielonego, 10 000/dzień dla Graph*). Limiter w cache (database/redis). Przekroczenie → `release(60)` |
| Retry | `tries = 3`, `backoff = [60, 300, 1800]`; miękkie odbicie (4xx SMTP) → ponów, twarde (5xx) → `bounced` |
| Deduplikacja | klucz idempotentny `delivery.uuid`; przed wysłaniem sprawdzamy `status = queued` (ochrona przed podwójną wysyłką po restarcie workera) |
| Worker | serwer: `php84 artisan queue:work database --queue=newsletter,default --tries=3 --max-time=3600` pod supervisorem |

\* Microsoft Graph ma limity dzienne na skrzynkę (ok. 10 000 adresatów/dzień) — dla list > 5 000 rekomendujemy SES lub Mailgun w regionie UE.

#### 1.5.3 Konfiguracja dostawców (panel → Ustawienia → Newsletter)

Wzorzec jak Microsoft SSO: wartości z `SiteSetting` wstrzykiwane w `AppServiceProvider::boot()` przez `config(['mail.mailers.ses.key' => …])`.

| Pole w `site_settings` | Cast | Uwagi |
|---|---|---|
| `newsletter_mailer` | string | `msgraph` / `ses` / `mailgun` / `postmark` / `smtp` |
| `newsletter_from_address`, `newsletter_from_name` | string | domyślnie `MAIL_FROM_*` |
| `newsletter_ses_key`, `newsletter_ses_secret`, `newsletter_ses_region` | `encrypted` | region UE (`eu-central-1`) |
| `newsletter_mailgun_domain`, `newsletter_mailgun_secret`, `newsletter_mailgun_endpoint` | `encrypted` | `api.eu.mailgun.net` |
| `newsletter_postmark_token` | `encrypted` | |
| `newsletter_rate_per_minute`, `newsletter_rate_per_day` | int | throttling |
| `newsletter_webhook_secret` | `encrypted` | podpis webhooków bounce |
| `newsletter_sms_provider`, `newsletter_sms_token`, `newsletter_sms_sender` | `encrypted` | opcjonalnie |
| `newsletter_turnstile_site_key`, `newsletter_turnstile_secret` | `encrypted` | opcjonalnie, domyślnie puste = wyłączone |
| `newsletter_doi_ttl_days` | int | domyślnie 7 |
| `newsletter_retention_days` | int | po ilu dniach anonimizować logi (domyślnie 730) |

Dodatkowo checklista „Dostarczalność” w panelu: SPF, DKIM, DMARC dla domeny nadawcy (sprawdzane rekordy DNS przez `dns_get_record`), adres `List-Unsubscribe`, domena śledzenia.

### 1.6 Analityka i raportowanie

| Wskaźnik | Definicja | Źródło |
|---|---|---|
| Dostarczone | `deliveries.status = delivered` (lub `sent` gdy dostawca nie raportuje) | `newsletter_deliveries` |
| Open rate | unikalne otwarcia / dostarczone | piksel `GET /n/o/{uuid}.gif` (1×1, `Cache-Control: no-store`); pierwsze otwarcie ustawia `opened_at`, każde dodaje wiersz `newsletter_opens` |
| CTR | unikalni klikający / dostarczone | `GET /n/c/{uuid}/{link_hash}` → zapis `newsletter_clicks` → 302 do oryginalnego URL (z UTM-ami kampanii) |
| CTOR | klikający / otwierający | pochodna |
| Bounce rate | twarde + miękkie odbicia / wysłane | webhooki SES SNS / Mailgun / Postmark → `ProcessBounceWebhook`; dla Graph: parser DSN ze skrzynki zwrotnej (`newsletter:ingest-bounces` czyta IMAP/Graph skrzynkę `bounce@`) |
| Rezygnacje | wypisy w 7 dni od wysyłki / dostarczone | `subscribers.unsubscribed_at` + `unsubscribe_campaign_id` |
| Skargi (FBL) | `complained` / dostarczone | webhook `complaint` |
| Mapa cieplna kliknięć | per link: liczba kliknięć i % | widok raportu nakłada na `html_body` (iframe) etykiety z `% kliknięć` przy każdym `<a>` (pozycja z `getBoundingClientRect`), kolor skali od `#DBE7FF` do `#1752BF` |
| Oś czasu | otwarcia/kliknięcia na godzinę przez 72 h | wykres słupkowy (Chart.js już w projekcie? → jeśli nie, SVG inline) |
| Porównanie kampanii | tabela OR/CTR/bounce dla N ostatnich | dashboard |
| Push/SMS | dostarczone (DLR), kliknięcia (link `/n/c/…`), błędy | `newsletter_deliveries` per kanał |

**Prywatność pomiaru:** otwarcia liczymy bez identyfikacji urządzenia (bez fingerprintu), IP pseudonimizowane (HMAC z kluczem aplikacji, obcięte do 16 znaków), User-Agent tylko jako sklasyfikowana rodzina (Gmail/Apple Mail/Outlook/inne). Apple Mail Privacy Protection sztucznie zawyża OR — raport oznacza „otwarcia proxy” flagą `is_proxy` (UA = Apple proxy) i pokazuje OR z nimi i bez nich.

### 1.7 Najnowsze aktualności w newsletterze (treść dynamiczna z CMS)

Redaktor nie przepisuje aktualności ręcznie — kampania może pobrać je z CMS (`App\Models\News`, scope `published()`, pola `title`, `excerpt`, `published_at`, obraz z Media Library, kategoria). Dwa tryby:

| Tryb | Jak działa | Zastosowanie |
|---|---|---|
| **Blok „Najnowsze aktualności”** w Mosaico | Nowy blok `latestNews` w szablonie `feer-1`. Redaktor przeciąga go do treści i ustawia w panelu bloku: źródło (aktualności / wydarzenia / blog / materiały), liczbę (1–12), kategorię (slug z `news_categories`), zakres („od ostatniej wysyłki” / 7 / 30 / 90 dni / bez ograniczenia), **sortowanie** (od najnowszych / **według kategorii** — pozycje pogrupowane pod nagłówkami kategorii z paskiem w kolorze kategorii / od najstarszych), układ (lista / kafle 2 kol.) i „dopasuj do tematów subskrybenta”. Blok w edytorze pokazuje **podgląd na żywo** (fetch `GET /admin/newsletter/content/news?limit=&category=&since=`), a przy wysyłce `ContentFeeder` renderuje go ponownie dla aktualnych danych. | newsletter miesięczny, przegląd tygodnia |
| **Ręczne wstawienie** | przycisk „Wstaw aktualność” w pasku edytora → modal z wyszukiwarką aktualności (tytuł, kategoria, data) → wstawia gotowy blok `singleArticle` z tytułem, zajawką, obrazem i linkiem (z UTM). Potem można go edytować jak zwykły tekst. | wyróżnienie jednej wiadomości |
| **Digest automatyczny** | kampania cykliczna (`newsletter_campaigns.recurrence`: `weekly` / `monthly`, dzień i godzina) oparta na szablonie z blokiem `latestNews`. Harmonogram tworzy kopię kampanii, wypełnia blok i wysyła, **tylko jeśli** od poprzedniej wysyłki pojawiła się ≥ 1 nowa aktualność (próg `min_items` konfigurowalny). Przy braku nowości — pomija i loguje. | „Co nowego w FEER” co piątek |

Definicja bloku w szablonie (placeholdery wypełnia backend; Mosaico traktuje `data-ko-container` jako listę):

```html
<table data-ko-block="latestNews" width="100%">
  <tr><td style="font-family: Montserrat, Arial, sans-serif; padding: 0 24px">
    <h2 data-ko-editable="heading" style="color:#1D1D1A; font-size:22px; font-weight:800">Najnowsze aktualności</h2>
    <!-- parametry bloku (widżety w @supports -ko-blockdefs):
         limit: select 1..6 | category: select (z CMS) | since: select (last_send|7|30|manual) | layout: list|grid -->
    <div data-ko-container="items" data-nl-feed="news" data-nl-limit="@limit" data-nl-category="@category" data-nl-since="@since">
      <!-- element listy renderowany przez ContentFeeder (markup identyczny z singleArticle) -->
      <table width="100%" style="margin: 0 0 20px"><tr>
        <td width="180" valign="top"><img src="{{item.image_url}}" width="180" alt="{{item.image_alt}}" style="border-radius:6px"></td>
        <td valign="top" style="padding-left:16px">
          <p style="margin:0 0 4px; font-size:13px; color:#4A4A47">{{item.published_at|date:d.m.Y}} · {{item.category}}</p>
          <h3 style="margin:0 0 8px; font-size:18px"><a href="{{item.url}}" style="color:#1752BF; font-weight:800">{{item.title}}</a></h3>
          <p style="margin:0 0 8px; font-size:15px; line-height:1.5; color:#1D1D1A">{{item.excerpt}}</p>
          <a href="{{item.url}}" style="color:#1752BF; font-weight:700">Czytaj dalej →</a>
        </td>
      </tr></table>
    </div>
    <p data-ko-display="items.length == 0" style="color:#4A4A47">Brak nowych aktualności w tym okresie.</p>
  </td></tr>
</table>
```

`ContentFeeder` (serwis w `Services/ContentFeeder.php`) obsługuje źródła: `news` (Aktualności), `events` (nadchodzące wydarzenia z `events`), `blog` (Wiem FEER — osobne połączenie `blog`), `materials` (materiały edukacyjne). Każde źródło zwraca jednolity DTO `FeedItem {title, excerpt, url, image_url, image_alt, published_at, category}`; link dostaje UTM-y kampanii i jest śledzony jak każdy inny (`/n/c/…`). Personalizacja per subskrybent: jeśli blok ma włączone „dopasuj do tematów subskrybenta”, lista jest filtrowana po jego `topics` (np. `etr` → tylko aktualności z kategorii ETR), co daje prosty, przejrzysty mechanizm bez profilowania.

W tabeli `newsletter_campaigns` dochodzą kolumny:

```sql
ALTER TABLE newsletter_campaigns
  ADD COLUMN recurrence      VARCHAR(16) NULL,        -- NULL | weekly | monthly
  ADD COLUMN recurrence_rule JSON        NULL,        -- {"weekday":5,"hour":9,"min_items":1,"sources":["news"]}
  ADD COLUMN parent_campaign_id BIGINT UNSIGNED NULL, -- kopia wygenerowana z kampanii cyklicznej
  ADD COLUMN content_snapshot JSON       NULL,        -- id aktualności faktycznie wysłanych (audyt, "od ostatniej wysyłki")
  ADD COLUMN last_feed_item_at TIMESTAMP NULL;        -- published_at najnowszej wysłanej pozycji
```

### 1.8 Dedykowany formularz zapisu (edytowalny w panelu)

Tabela `newsletter_forms` i panel **Newsletter → Formularze zapisu**. Jeden formularz jest domyślny — wyświetla go strona systemowa **`/newsletter`** (oraz `/newsletter/{slug}` dla pozostałych) i sekcja newslettera na stronie głównej szablonów. Pola edytowalne: nadtytuł, nagłówek, lead, napis przycisku, komunikat po wysłaniu, pola (imię, telefon), tematy do wyboru i zaznaczone domyślnie, zgoda SMS, treść zgody + powiązana klauzula `GdprClause`, link do polityki, układ (pasek / karta / linia), kolor akcentu, listy docelowe, slug formularza w SZO, aktywność. Osadzenie w dowolnym widoku Blade:

```blade
<x-newsletter-widget />                                   {{-- domyślny formularz --}}
<x-newsletter-widget :form="$form" style="card" source="stopka" />
```

Komponent generuje żeton i zadanie `SpamGuard`, zapisuje zgodę ze snapshotem klauzuli i wersją (`updated_at` formularza), inkrementuje licznik zgłoszeń. Przy pierwszym uruchomieniu moduł sam tworzy formularz domyślny, więc `/newsletter` działa od razu.

### 1.9 Połączenie z SZO i CRM

| Kierunek | Mechanizm | Konfiguracja |
|---|---|---|
| **SZO (feerSZO)** | Po potwierdzeniu DOI (i przy zmianie preferencji) job `SyncSubscriberToCrm` wysyła `POST {szo.url}/api/v1/forms.php` z `form` = slug formularza SZO (z formularza zapisu albo domyślny z ustawień), `data` (email, imię, telefon, notatka z tematami), `consents: ["rodo","newsletter"]`, `meta.external_id = "newsletter:{uuid}"` (idempotencja). Wynik w `subscribers.szo_contact_id / szo_synced_at / szo_error`. | Ustawienia → Integracje → SZO (adres, token) + Newsletter → Ustawienia → „SZO i CRM” |
| **CRM (webhook)** | Zdarzenia `subscribed`, `confirmed`, `preferences_changed`, `unsubscribed`, `anonymized` jako JSON `{event, occurred_at, subscriber{…}, consents[]}` podpisany `X-Signature: sha256=HMAC(body, secret)`. | adres + sekret w ustawieniach newslettera |
| **Ponowienia** | `php84 artisan newsletter:sync-crm` (harmonogram co 15 min) dosyła niezsynchronizowanych; przycisk „Synchronizuj z SZO/CRM” na karcie subskrybenta. | — |

---

## 2. Zgodność z prawem i bezpieczeństwo

### 2.1 RODO / GDPR i Prawo komunikacji elektronicznej (PKE)

| Wymóg | Mechanizm |
|---|---|
| Podstawa prawna: zgoda (art. 6 ust. 1 lit. a RODO; art. 398 PKE — informacja handlowa) | checkbox zgody niezaznaczony domyślnie; osobne zgody per kanał (e-mail / push / SMS); treść zgody wersjonowana w `GdprClause` (istniejący model) |
| Double opt-in | zapis → `pending` → e-mail z `{{confirm_url}}` (token 48 zn., ważny `newsletter_doi_ttl_days`) → `confirmed_at`. Ponowny zapis po wypisie znów wymaga DOI. Nie wysyłamy nic poza DOI dopóki `pending`. |
| Rejestr zgód | `newsletter_consents`: kto, kiedy (UTC), jaki tekst (`clause_version`), kanał, źródło (`widget_home`, `form_event_123`, `import`), pseudonim IP, User-Agent, sposób potwierdzenia (`doi_click`, `admin_import`), oraz wycofanie (`revoked_at`, `revoked_via`) |
| Łatwy wypis | link w każdej wiadomości, nagłówki `List-Unsubscribe: <mailto:…>, <https://…/n/u/{token}>` i `List-Unsubscribe-Post: List-Unsubscribe=One-Click` (RFC 8058; Gmail/Yahoo wymagają od 2024); strona wypisu bez logowania; wypis skutkuje natychmiast, także dla kampanii w trakcie wysyłki (job sprawdza status przed wysłaniem) |
| Prawo dostępu / przenoszenia | karta subskrybenta → „Eksportuj dane osoby” (JSON: profil, zgody, dostawy, aktywność); samoobsługa: strona preferencji ma „Pobierz moje dane” |
| Prawo do usunięcia | „Anonimizuj”: e-mail → `deleted-{id}@anonymized.invalid`, `email_hash` zachowany 30 dni na liście tłumienia (żeby nie wysłać ponownie), imię/telefon/IP → NULL, logi zachowane bez PII do statystyk |
| Minimalizacja i retencja | logi otwarć/kliknięć starsze niż `newsletter_retention_days` agregowane do liczników i usuwane (`newsletter:prune` w harmonogramie); `pending` > TTL → `expired`, po 30 dniach usuwane |
| Rozliczalność | każda operacja admina na danych subskrybenta (eksport, import, anonimizacja, zmiana statusu) w `activity_log` (Spatie, już w projekcie) |
| Powierzenie przetwarzania | lista procesorów w ustawieniach (dostawca e-mail, SMS) z linkiem do DPA; rekomendacja regionów UE (SES `eu-central-1`, Mailgun EU, Postmark — USA, wymaga SCC) |
| Informacja przy zbieraniu (art. 13) | pod formularzem skrót + link do polityki prywatności; pełna treść klauzuli z `GdprClause` |
| Dzieci | brak zbierania wieku; w regulaminie informacja 16+ |

### 2.2 Bezpieczeństwo danych

| Warstwa | Rozwiązanie |
|---|---|
| Szyfrowanie w spoczynku | (a) dysk/wolumen serwera (LUKS) + kopie zapasowe szyfrowane; (b) na poziomie aplikacji: `subscribers.email`, `name`, `phone` z castem `encrypted`, wyszukiwanie przez **ślepy indeks** `email_hash = HMAC-SHA256(lower(email), APP_KEY)` (kolumna unikalna). Kompromis: brak wyszukiwania `LIKE` po fragmencie e-maila — w panelu szukamy po pełnym adresie lub po `name_search` (znormalizowane, bez PII?). **Rekomendacja:** w pierwszej iteracji szyfrować `phone` i IP, e-mail trzymać jawnie z ślepym indeksem gotowym do migracji; decyzję podjąć po DPIA. |
| Sekrety dostawców | `SiteSetting` cast `encrypted` (AES-256-CBC, `APP_KEY`) — jak dziś `cleantalk_access_key` |
| Tokeny | `token` (48 zn., `Str::random`), `uuid` dostaw (v4); porównania przez indeks, nigdy przez `LIKE`; tokeny wypisu nie wygasają, tokeny DOI tak |
| Ochrona formularza | `SpamGuard` (honeypot `website`, żeton czasowy `form_check` ≥ 3 s, zadanie tekstowe `form_answer`) + `throttle:5,1` na IP + limit 3 DOI/adres/24 h + opcjonalnie Cloudflare Turnstile (niewidoczny, bez zadań obrazkowych; po włączeniu zastępuje zadanie tekstowe). **Nie** reCAPTCHA v2 (bariera WCAG). |
| Enumeracja adresów | formularz zawsze odpowiada „sprawdź skrzynkę”, niezależnie czy adres istnieje |
| Webhooki | weryfikacja podpisu (SES: SNS signature, Mailgun: HMAC, Postmark: basic auth + IP allowlist); idempotencja po `provider_message_id` |
| Tracking | `/n/*` bez sesji i bez cookies; parametr `link_hash` = HMAC(url) → brak open redirect |
| XSS w treści | HTML z Mosaico sanityzowany `App\Support\SafeHtml` przed podglądem w panelu; personalizowane wartości escapowane |
| Uprawnienia | role: `newsletter.view`, `newsletter.edit`, `newsletter.send`, `newsletter.subscribers.export`, `newsletter.settings`; wysyłka do > 1 000 odbiorców wymaga potwierdzenia hasłem/2FA (jest Google2FA) |
| Audyt | `activity_log` + log wysyłek; dzienny raport e-mail do admina (`UnreadContactDigestMail` jako wzorzec) |

---

## 3. Schemat bazy danych

Dialekt MySQL 8 / MariaDB 10.6 (produkcja). Lokalnie SQLite — migracje Laravel pominą `JSON`-owe indeksy i `ENUM` (używamy `string` + walidacja w modelu). Pola `site_id` → `site_settings.id`.

### 3.1 `subscribers` — rozszerzenie istniejącej tabeli

```sql
ALTER TABLE subscribers
  ADD COLUMN site_id            BIGINT UNSIGNED NULL AFTER id,
  ADD COLUMN uuid               CHAR(36)        NOT NULL,
  ADD COLUMN email_hash         CHAR(64)        NOT NULL,              -- HMAC-SHA256, ślepy indeks
  ADD COLUMN phone              VARCHAR(255)    NULL,                  -- szyfrowany (cast encrypted), E.164
  ADD COLUMN status             VARCHAR(20)     NOT NULL DEFAULT 'pending',
  ADD COLUMN channels           JSON            NULL,                  -- ["email","webpush","sms"]
  ADD COLUMN tags               JSON            NULL,
  ADD COLUMN locale             VARCHAR(5)      NOT NULL DEFAULT 'pl',
  ADD COLUMN timezone           VARCHAR(64)     NOT NULL DEFAULT 'Europe/Warsaw',
  ADD COLUMN source             VARCHAR(64)     NULL,                  -- widget_home, page_123, import, admin
  ADD COLUMN confirmation_sent_at TIMESTAMP     NULL,
  ADD COLUMN unsubscribed_at    TIMESTAMP       NULL,
  ADD COLUMN unsubscribe_reason VARCHAR(255)    NULL,
  ADD COLUMN unsubscribe_campaign_id BIGINT UNSIGNED NULL,
  ADD COLUMN bounced_at         TIMESTAMP       NULL,
  ADD COLUMN complained_at      TIMESTAMP       NULL,
  ADD COLUMN last_sent_at       TIMESTAMP       NULL,
  ADD COLUMN last_open_at       TIMESTAMP       NULL,
  ADD COLUMN last_click_at      TIMESTAMP       NULL,
  ADD COLUMN engagement_score   SMALLINT        NOT NULL DEFAULT 0,    -- 0–100, liczony nocą
  ADD COLUMN anonymized_at      TIMESTAMP       NULL,
  ADD UNIQUE KEY subscribers_uuid_unique (uuid),
  ADD UNIQUE KEY subscribers_email_hash_unique (email_hash),
  ADD KEY subscribers_site_status_idx (site_id, status),
  ADD KEY subscribers_last_open_idx (last_open_at),
  ADD CONSTRAINT subscribers_site_fk FOREIGN KEY (site_id) REFERENCES site_settings(id) ON DELETE SET NULL;

-- migracja danych: status z confirmed_at
UPDATE subscribers SET status = IF(confirmed_at IS NULL, 'pending', 'confirmed'),
                       uuid = UUID(), channels = JSON_ARRAY('email');
```

### 3.2 Listy, segmenty, zgody

```sql
CREATE TABLE newsletter_lists (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  site_id       BIGINT UNSIGNED NULL,
  name          VARCHAR(120)  NOT NULL,
  slug          VARCHAR(120)  NOT NULL,
  description   TEXT          NULL,
  is_public     TINYINT(1)    NOT NULL DEFAULT 0,   -- widoczna w preferencjach subskrybenta
  created_at    TIMESTAMP NULL, updated_at TIMESTAMP NULL,
  UNIQUE KEY newsletter_lists_site_slug (site_id, slug)
) ENGINE=InnoDB;

CREATE TABLE newsletter_list_subscriber (
  list_id       BIGINT UNSIGNED NOT NULL,
  subscriber_id BIGINT UNSIGNED NOT NULL,
  added_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  added_by      BIGINT UNSIGNED NULL,               -- users.id lub NULL (self-service)
  PRIMARY KEY (list_id, subscriber_id),
  FOREIGN KEY (list_id) REFERENCES newsletter_lists(id) ON DELETE CASCADE,
  FOREIGN KEY (subscriber_id) REFERENCES subscribers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE newsletter_segments (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  site_id       BIGINT UNSIGNED NULL,
  name          VARCHAR(120) NOT NULL,
  rules         JSON         NOT NULL,              -- patrz §1.3
  cached_count  INT UNSIGNED NULL,
  counted_at    TIMESTAMP    NULL,
  created_at    TIMESTAMP NULL, updated_at TIMESTAMP NULL
) ENGINE=InnoDB;

CREATE TABLE newsletter_consents (                  -- rejestr zgód (append-only)
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  subscriber_id   BIGINT UNSIGNED NOT NULL,
  channel         VARCHAR(20)  NOT NULL,            -- email | webpush | sms
  clause_id       BIGINT UNSIGNED NULL,             -- gdpr_clauses.id
  clause_version  VARCHAR(20)  NOT NULL,
  clause_text     TEXT         NOT NULL,            -- snapshot treści zgody
  source          VARCHAR(64)  NOT NULL,            -- widget_home, page_123, import, admin
  method          VARCHAR(32)  NOT NULL,            -- doi_click | admin_import | preferences
  ip_hash         CHAR(16)     NULL,
  user_agent      VARCHAR(255) NULL,
  granted_at      TIMESTAMP    NOT NULL,
  confirmed_at    TIMESTAMP    NULL,                -- klik DOI
  revoked_at      TIMESTAMP    NULL,
  revoked_via     VARCHAR(32)  NULL,                -- link | one_click | preferences | admin | bounce
  FOREIGN KEY (subscriber_id) REFERENCES subscribers(id) ON DELETE CASCADE,
  KEY newsletter_consents_sub_channel (subscriber_id, channel)
) ENGINE=InnoDB;
```

### 3.3 Szablony i kampanie

```sql
CREATE TABLE newsletter_templates (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  site_id         BIGINT UNSIGNED NULL,
  name            VARCHAR(120) NOT NULL,
  kind            VARCHAR(16)  NOT NULL DEFAULT 'mosaico',   -- mosaico | html | text
  mosaico_template VARCHAR(64) NULL,                         -- np. feer-1 (katalog w resources/mosaico/templates)
  editor_metadata JSON         NULL,                         -- Mosaico exportMetadata()
  editor_content  JSON         NULL,                         -- Mosaico exportJSON()
  html_body       MEDIUMTEXT   NULL,                         -- wyrenderowany HTML (dla kind=html: źródło)
  thumbnail_path  VARCHAR(255) NULL,
  is_default      TINYINT(1)   NOT NULL DEFAULT 0,
  created_by      BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, deleted_at TIMESTAMP NULL
) ENGINE=InnoDB;

CREATE TABLE newsletter_campaigns (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  site_id         BIGINT UNSIGNED NULL,
  uuid            CHAR(36)     NOT NULL,
  title           VARCHAR(160) NOT NULL,                      -- nazwa robocza
  subject         VARCHAR(255) NOT NULL,
  subject_b       VARCHAR(255) NULL,                          -- test A/B tematu
  ab_split_percent TINYINT UNSIGNED NULL,                     -- np. 20 (10% A, 10% B, reszta zwycięzca)
  preheader       VARCHAR(255) NULL,
  from_name       VARCHAR(120) NULL,
  from_address    VARCHAR(255) NULL,
  reply_to        VARCHAR(255) NULL,
  template_id     BIGINT UNSIGNED NULL,
  editor_metadata JSON         NULL,
  editor_content  JSON         NULL,
  html_body       MEDIUMTEXT   NULL,
  text_body       MEDIUMTEXT   NULL,                          -- auto z HTML, edytowalny
  short_title     VARCHAR(60)  NULL,                          -- push
  short_text      VARCHAR(160) NULL,                          -- push / SMS
  short_url       VARCHAR(500) NULL,
  channels        JSON         NOT NULL,                      -- ["email"] | ["email","webpush"] …
  audience        JSON         NOT NULL,                      -- {"lists":[1,2],"segments":[3],"exclude_lists":[9]}
  utm             JSON         NULL,                          -- {"source":"newsletter","medium":"email","campaign":"2026-10"}
  track_opens     TINYINT(1)   NOT NULL DEFAULT 1,
  track_clicks    TINYINT(1)   NOT NULL DEFAULT 1,
  status          VARCHAR(20)  NOT NULL DEFAULT 'draft',      -- draft|scheduled|queued|sending|paused|sent|cancelled|failed
  scheduled_at    TIMESTAMP    NULL,
  send_in_recipient_tz TINYINT(1) NOT NULL DEFAULT 0,
  started_at      TIMESTAMP    NULL,
  finished_at     TIMESTAMP    NULL,
  batch_id        VARCHAR(36)  NULL,                          -- job_batches.id
  recipients_count INT UNSIGNED NOT NULL DEFAULT 0,
  -- liczniki zdenormalizowane (odświeżane przez joby / webhooki)
  sent_count INT UNSIGNED NOT NULL DEFAULT 0,
  delivered_count INT UNSIGNED NOT NULL DEFAULT 0,
  opened_unique INT UNSIGNED NOT NULL DEFAULT 0,
  clicked_unique INT UNSIGNED NOT NULL DEFAULT 0,
  bounced_count INT UNSIGNED NOT NULL DEFAULT 0,
  complained_count INT UNSIGNED NOT NULL DEFAULT 0,
  unsubscribed_count INT UNSIGNED NOT NULL DEFAULT 0,
  created_by      BIGINT UNSIGNED NULL,
  approved_by     BIGINT UNSIGNED NULL,                       -- opcjonalny 4-eyes przed wysyłką
  created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, deleted_at TIMESTAMP NULL,
  UNIQUE KEY newsletter_campaigns_uuid (uuid),
  KEY newsletter_campaigns_status_sched (status, scheduled_at),
  FOREIGN KEY (template_id) REFERENCES newsletter_templates(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE newsletter_campaign_links (                       -- słownik linków (mapa cieplna)
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  campaign_id   BIGINT UNSIGNED NOT NULL,
  hash          CHAR(16)     NOT NULL,                         -- HMAC(url) skrócony
  url           VARCHAR(2048) NOT NULL,
  label         VARCHAR(255) NULL,                             -- tekst linku / alt obrazka
  position      SMALLINT UNSIGNED NULL,                        -- kolejność w HTML
  clicks_total  INT UNSIGNED NOT NULL DEFAULT 0,
  clicks_unique INT UNSIGNED NOT NULL DEFAULT 0,
  UNIQUE KEY newsletter_links_campaign_hash (campaign_id, hash),
  FOREIGN KEY (campaign_id) REFERENCES newsletter_campaigns(id) ON DELETE CASCADE
) ENGINE=InnoDB;
```

### 3.4 Logi wysyłki i analityka

```sql
CREATE TABLE newsletter_deliveries (                           -- = email_logs; 1 wiersz = 1 odbiorca × 1 kanał
  id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  uuid                CHAR(36)     NOT NULL,                   -- w pikselu, linkach, webversion
  campaign_id         BIGINT UNSIGNED NOT NULL,
  subscriber_id       BIGINT UNSIGNED NOT NULL,
  channel             VARCHAR(20)  NOT NULL,                   -- email | webpush | sms
  variant             CHAR(1)      NULL,                       -- A | B
  status              VARCHAR(20)  NOT NULL DEFAULT 'queued',  -- queued|sent|delivered|soft_bounced|hard_bounced|complained|failed|skipped
  provider            VARCHAR(32)  NULL,                       -- msgraph | ses | mailgun | webpush | smsapi
  provider_message_id VARCHAR(255) NULL,
  error_code          VARCHAR(64)  NULL,
  error_message       VARCHAR(500) NULL,
  attempts            TINYINT UNSIGNED NOT NULL DEFAULT 0,
  queued_at           TIMESTAMP NULL,
  sent_at             TIMESTAMP NULL,
  delivered_at        TIMESTAMP NULL,
  opened_at           TIMESTAMP NULL,                          -- pierwsze otwarcie
  clicked_at          TIMESTAMP NULL,                          -- pierwsze kliknięcie
  opens_count         SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  clicks_count        SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  UNIQUE KEY newsletter_deliveries_uuid (uuid),
  UNIQUE KEY newsletter_deliveries_unique (campaign_id, subscriber_id, channel),
  KEY newsletter_deliveries_status (campaign_id, status),
  KEY newsletter_deliveries_provider_msg (provider_message_id),
  FOREIGN KEY (campaign_id) REFERENCES newsletter_campaigns(id) ON DELETE CASCADE,
  FOREIGN KEY (subscriber_id) REFERENCES subscribers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE newsletter_opens (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  delivery_id   BIGINT UNSIGNED NOT NULL,
  opened_at     TIMESTAMP    NOT NULL,
  ip_hash       CHAR(16)     NULL,
  client_family VARCHAR(32)  NULL,                             -- gmail | apple_mail | outlook | other
  is_proxy      TINYINT(1)   NOT NULL DEFAULT 0,               -- Apple MPP / Gmail image proxy
  KEY newsletter_opens_delivery (delivery_id, opened_at),
  FOREIGN KEY (delivery_id) REFERENCES newsletter_deliveries(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE newsletter_clicks (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  delivery_id   BIGINT UNSIGNED NOT NULL,
  link_id       BIGINT UNSIGNED NOT NULL,
  clicked_at    TIMESTAMP    NOT NULL,
  ip_hash       CHAR(16)     NULL,
  user_agent    VARCHAR(255) NULL,
  KEY newsletter_clicks_delivery (delivery_id),
  KEY newsletter_clicks_link (link_id, clicked_at),
  FOREIGN KEY (delivery_id) REFERENCES newsletter_deliveries(id) ON DELETE CASCADE,
  FOREIGN KEY (link_id) REFERENCES newsletter_campaign_links(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE newsletter_bounces (
  id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  delivery_id         BIGINT UNSIGNED NULL,                    -- NULL gdy nie da się dopasować
  subscriber_id       BIGINT UNSIGNED NULL,
  type                VARCHAR(16)  NOT NULL,                   -- hard | soft | complaint | dlr_failed (sms)
  provider            VARCHAR(32)  NOT NULL,
  provider_event_id   VARCHAR(255) NULL,                       -- idempotencja webhooka
  smtp_code           VARCHAR(10)  NULL,
  reason              VARCHAR(500) NULL,
  raw_payload         JSON         NULL,                       -- bez PII poza adresem; czyszczone po retencji
  occurred_at         TIMESTAMP    NOT NULL,
  processed_at        TIMESTAMP    NULL,
  UNIQUE KEY newsletter_bounces_event (provider, provider_event_id),
  KEY newsletter_bounces_subscriber (subscriber_id),
  FOREIGN KEY (delivery_id) REFERENCES newsletter_deliveries(id) ON DELETE SET NULL,
  FOREIGN KEY (subscriber_id) REFERENCES subscribers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE newsletter_subscriber_events (                    -- historia aktywności (oś czasu)
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  subscriber_id BIGINT UNSIGNED NOT NULL,
  type          VARCHAR(40)  NOT NULL,   -- subscribed|doi_sent|confirmed|preferences_changed|unsubscribed|
                                         -- bounced|complained|imported|exported|anonymized|status_changed
  campaign_id   BIGINT UNSIGNED NULL,
  actor_id      BIGINT UNSIGNED NULL,    -- users.id gdy admin
  meta          JSON         NULL,
  created_at    TIMESTAMP    NOT NULL,
  KEY newsletter_events_subscriber (subscriber_id, created_at),
  FOREIGN KEY (subscriber_id) REFERENCES subscribers(id) ON DELETE CASCADE
) ENGINE=InnoDB;
```

**Reguła tłumienia** — przed wysyłką `SendDelivery` sprawdza: `status = confirmed` ∧ kanał w `channels` ∧ ważna zgoda dla kanału ∧ brak `email_hash` w `newsletter_suppressions` (lista domen/adresów blokowanych + zanonimizowane, 30 dni). Tabela `newsletter_suppressions(email_hash, reason, expires_at)`.

### 3.5 Relacje (skrót)

```
site_settings 1──∞ subscribers 1──∞ newsletter_consents
                     │ ∞                 
                     ├──∞ newsletter_list_subscriber ∞──1 newsletter_lists
                     ├──∞ newsletter_deliveries ∞──1 newsletter_campaigns ∞──1 newsletter_templates
                     │        ├──∞ newsletter_opens
                     │        ├──∞ newsletter_clicks ∞──1 newsletter_campaign_links ∞──1 newsletter_campaigns
                     │        └──∞ newsletter_bounces
                     └──∞ newsletter_subscriber_events
push_subscriptions ∞──1 subscribers   (nowa kolumna subscriber_id NULL)
```

---

## 4. UI/UX i przepływy użytkownika

Konwencje panelu bez zmian: `@extends('admin.layout')`, Tailwind, inputy `rounded border-gray-300 focus:border-brand focus:ring-brand`, label `mb-1 block text-sm font-bold`, zakładki Alpine `x-data="{ tab }"`, przyciski `bg-brand … hover:bg-brand-dark`. Nawigacja: nowa grupa **Newsletter** w menu admina z pozycjami: Pulpit · Kampanie · Subskrybenci · Listy i segmenty · Szablony · Raporty · Ustawienia.

### 4.1 Dashboard (`/admin/newsletter`)

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ Newsletter · Pulpit                                     [ + Nowa kampania ]  │
├────────────┬────────────┬────────────┬────────────┬──────────────────────────┤
│ Aktywni    │ Nowi 30 dni│ Śr. OR     │ Śr. CTR    │ Odbicia 30 dni           │
│ 4 812      │ +163 ▲     │ 38,2 %     │ 6,9 %      │ 0,4 %  ● w normie        │
├────────────┴────────────┴────────────┴────────────┴──────────────────────────┤
│ Ostatnie kampanie                                   │ Zaplanowane            │
│ ─────────────────────────────────────────────────── │ ────────────────────── │
│ Nazwa          Wysłano   Dost.  OR     CTR   Stan   │ 12.10 09:00 Aktualn.   │
│ Październik    03.10     4 790  41 %   7 %   ✔ sent │ 15.10 10:00 Webinar    │
│ Webinar ETR    26.09     1 203  52 %   12 %  ✔ sent │ [Zobacz kolejkę]       │
├─────────────────────────────────────────────────────┴────────────────────────┤
│ Wzrost bazy (90 dni) — wykres liniowy │ Stan kolejki: 0 w toku · worker OK   │
│ Dostarczalność: SPF ✔ DKIM ✔ DMARC ⚠ (p=none) → [Jak naprawić]              │
└──────────────────────────────────────────────────────────────────────────────┘
```

Kafle KPI: `role="group" aria-labelledby`, wartości w `<dl>`; wykres SVG z tabelą danych w `<details>` (dostępna alternatywa).

### 4.2 Lista subskrybentów (`/admin/newsletter/subskrybenci`)

- Pasek narzędzi: szukaj (pełny e-mail / imię), filtry: status, temat, kanał, lista, segment, ośrodek, data zapisu; akcje zbiorcze: dodaj do listy, zmień tagi, wypisz, anonimizuj (modal z potwierdzeniem i liczbą rekordów); przyciski **Import** i **Eksport**.
- Tabela: E-mail · Imię · Status (badge z ikoną i tekstem, nie tylko kolor) · Kanały (ikony z `aria-label`) · Tematy · Zaangażowanie (pasek 0–100 + wartość) · Zapisany · Ostatnie otwarcie · Akcje.
- Rozwinięcie wiersza / karta subskrybenta: zakładki *Profil* · *Zgody* · *Historia* (oś czasu) · *Dostawy*.

### 4.3 Kreator kampanii — 5 kroków (stepper u góry, `aria-current="step"`)

| Krok | Zawartość | Walidacja przed przejściem |
|---|---|---|
| **1. Podstawy** | nazwa robocza, temat (+ licznik znaków i podgląd w skrzynce: nadawca / temat / preheader jak w Gmail), preheader, nadawca, reply-to, test A/B tematu (przełącznik → `subject_b` + suwak % próbki) | temat ≤ 78 zn., nadawca zweryfikowany |
| **2. Odbiorcy** | wybór list (chips) i segmentów, wykluczenia; panel po prawej: „Odbiorców: **3 214**” odświeżany na żywo (debounce 500 ms, `aria-live="polite"`); kanały: ☑ E-mail ☐ Web push ☐ SMS (SMS pokazuje koszt szacunkowy) | ≥ 1 odbiorca, ≥ 1 kanał |
| **3. Treść** | wybór szablonu (galeria miniatur) → pełnoekranowy edytor Mosaico; pod spodem sekcja „Wersja skrócona (push/SMS)” pokazywana gdy wybrano te kanały: tytuł (60), tekst (160, licznik), link; przyciski „Wstaw tag” (dropdown z `{{…}}`) i „Wstaw aktualność” (modal z wyszukiwarką, §1.7); blok „Najnowsze aktualności” w palecie bloków; walidacja kontrastu | HTML zawiera `{{unsubscribe_url}}`; brak pustych linków; obrazki mają `alt` (ostrzeżenie) |
| **4. Test i sprawdzenie** | podgląd desktop/mobile/tekst; wyślij test na adresy (max 5); checklista automatyczna: link wypisu ✔, alt-y ✔, waga HTML < 100 kB ✔, spam-words ⚠, linki działają (HEAD) ✔; podgląd jak zobaczy to konkretny subskrybent (personalizacja) | wszystkie pozycje ✔ lub świadome „wyślij mimo to” |
| **5. Wysyłka** | natychmiast / zaplanuj (data, godzina, „o lokalnej godzinie odbiorcy”); podsumowanie: odbiorcy, kanały, dostawca, szacowany czas (odbiorcy ÷ limit/min); przycisk **Wyślij** z modalem potwierdzającym (`Wysyłasz do 3 214 osób` + wpisanie słowa WYŚLIJ gdy > 1 000) | uprawnienie `newsletter.send` |

Po wysłaniu: ekran postępu (pasek, wysłane/odrzucone, ETA, `aria-live`), przyciski *Wstrzymaj* / *Anuluj*.

### 4.4 Raport kampanii

Nagłówek z 6 KPI (dostarczone, OR, CTR, CTOR, odbicia, wypisy) + porównanie z średnią; zakładki: *Oś czasu* (72 h), *Linki* (tabela + mapa cieplna nad podglądem HTML), *Odbiorcy* (lista z filtrem otworzył/kliknął/odbił), *Kanały* (e-mail vs push vs SMS), *Eksport* (CSV/PDF przez istniejący DomPDF).

### 4.5 Przepływ subskrybenta (frontend)

```
Widget (home/stopka)  ─► POST /newsletter/zapis  ─► "Sprawdź skrzynkę" (zawsze ten sam ekran)
                                                      │
                            e-mail DOI ◄──────────────┘
                              │ klik {{confirm_url}} (≤ 7 dni)
                              ▼
                    GET /n/potwierdz/{token} ─► status confirmed ─► strona "Dziękujemy" + link do preferencji
                                                                      (opcjonalnie: przycisk "Włącz powiadomienia push")
Każda wiadomość:  {{unsubscribe_url}} ─► GET /n/u/{token} (potwierdzenie 1 klik) / POST one-click ─► wypisany
                  {{preferences_url}} ─► GET /n/p/{token} ─► tematy, kanały, częstotliwość, "Pobierz moje dane", "Usuń mnie"
```

Ekrany publiczne dziedziczą `layouts/site.blade.php` (szablon ośrodka), więc wyglądają spójnie z resztą witryny.

### 4.6 Widget zapisu — HTML/CSS/JS

Samodzielny komponent do osadzenia w partialach `templates/*/partials/home/newsletter.blade.php` (zastępuje embed z `newsletter_code`, który zostaje jako fallback dla zewnętrznych narzędzi). Cechy:

- brandbook: Montserrat, `#1D1D1A` tekst, `#1752BF` przycisk/link (5,8:1 na bieli), `#1E6DFF` dekoracja/ramka fokusu, `#EA8F00` pasek akcentu, płaski, kontrastowy;
- WCAG 2.2 AA: widoczne etykiety, `autocomplete`, błędy powiązane `aria-describedby`, komunikaty w `aria-live`, fokus 3 px, cel dotyku ≥ 44 px, działa bez JS (zwykły POST), `prefers-reduced-motion`;
- antyspam: honeypot `website`, żeton `form_check`, zadanie tekstowe `form_answer` (z `SpamGuard::challenge()`);
- stany: domyślny → wysyłanie (przycisk zablokowany, tekst „Zapisuję…”) → sukces (formularz zamieniony na komunikat) → błąd (komunikat pod polem).

Implementacja: komponent `modules/Newsletter/resources/views/components/signup-widget.blade.php` (`<x-newsletter-widget>`), klasa `Modules\Newsletter\View\Components\SignupWidget`.

```html
<!-- resources/views/components/newsletter-widget.blade.php (wersja statyczna poniżej) -->
<section class="nlw" aria-labelledby="nlw-title">
  <div class="nlw__inner">
    <div class="nlw__copy">
      <p class="nlw__eyebrow">Newsletter FEER</p>
      <h2 id="nlw-title" class="nlw__title">Bądź na bieżąco z tym, co robimy</h2>
      <p class="nlw__lead">Raz w miesiącu: aktualności, szkolenia, materiały edukacyjne. Bez spamu, wypis jednym kliknięciem.</p>
    </div>

    <form class="nlw__form" action="/newsletter/zapis" method="post" novalidate data-nlw-form>
      <input type="hidden" name="_token" value="{{ csrf_token() }}">
      <input type="hidden" name="source" value="widget_home">
      <input type="hidden" name="form_check" value="{{ $challenge['token'] }}">

      <!-- honeypot: poza ekranem, pomijany przez AT -->
      <div class="nlw__hp" aria-hidden="true">
        <label for="nlw-website">Strona WWW</label>
        <input id="nlw-website" type="text" name="website" tabindex="-1" autocomplete="off">
      </div>

      <div class="nlw__row">
        <div class="nlw__field">
          <label for="nlw-name" class="nlw__label">Imię <span class="nlw__opt">(opcjonalnie)</span></label>
          <input id="nlw-name" class="nlw__input" type="text" name="name" autocomplete="given-name" maxlength="100">
        </div>
        <div class="nlw__field">
          <label for="nlw-email" class="nlw__label">Adres e-mail</label>
          <input id="nlw-email" class="nlw__input" type="email" name="email" autocomplete="email"
                 required aria-required="true" aria-describedby="nlw-email-err" inputmode="email">
          <p id="nlw-email-err" class="nlw__error" hidden></p>
        </div>
      </div>

      <fieldset class="nlw__topics">
        <legend class="nlw__label">Co Cię interesuje?</legend>
        <label class="nlw__chip"><input type="checkbox" name="topics[]" value="news" checked> Aktualności</label>
        <label class="nlw__chip"><input type="checkbox" name="topics[]" value="events"> Szkolenia i wydarzenia</label>
        <label class="nlw__chip"><input type="checkbox" name="topics[]" value="materials"> Materiały edukacyjne</label>
        <label class="nlw__chip"><input type="checkbox" name="topics[]" value="etr"> Treści ETR</label>
      </fieldset>

      <div class="nlw__field">
        <label for="nlw-answer" class="nlw__label">Zabezpieczenie: {{ $challenge['question'] }}</label>
        <input id="nlw-answer" class="nlw__input nlw__input--short" type="text" name="form_answer"
               required aria-required="true" autocomplete="off" aria-describedby="nlw-answer-help">
        <p id="nlw-answer-help" class="nlw__help">Wpisz wynik słownie lub cyfrą.</p>
      </div>

      <label class="nlw__consent">
        <input type="checkbox" name="consent" value="1" required aria-required="true" aria-describedby="nlw-consent-err">
        <span>Chcę otrzymywać newsletter FEER na podany adres. Wiem, że mogę się wypisać w każdej chwili.
          <a href="/polityka-prywatnosci">Jak przetwarzamy dane</a>.</span>
      </label>
      <p id="nlw-consent-err" class="nlw__error" hidden></p>

      <button type="submit" class="nlw__btn" data-nlw-submit>
        <span data-nlw-label>Zapisz się</span>
      </button>

      <p class="nlw__status" role="status" aria-live="polite" data-nlw-status></p>
    </form>
  </div>
</section>
```

```css
/* resources/css/newsletter-widget.css — tokeny spójne z app.css (--color-brand*) */
.nlw {
  --nlw-text: #1D1D1A;
  --nlw-brand: var(--color-brand, #1E6DFF);
  --nlw-brand-dark: var(--color-brand-dark, #1752BF);  /* 5,8:1 na bieli */
  --nlw-accent: #EA8F00;
  --nlw-bg: #FFFFFF;
  --nlw-muted: #4A4A47;                                 /* 8,2:1 */
  --nlw-border: #B9B9B6;
  --nlw-error: #B3261E;                                 /* 6,6:1 */
  --nlw-ok-bg: #E6F4EA; --nlw-ok: #0F5132;
  font-family: Montserrat, Arial, Helvetica, sans-serif;
  color: var(--nlw-text);
  background: var(--nlw-bg);
  border-top: 6px solid var(--nlw-accent);
  padding: clamp(32px, 6vw, 64px) 16px;
}
.nlw__inner { max-width: 1040px; margin: 0 auto; display: grid; gap: 32px; grid-template-columns: 1fr; }
@media (min-width: 880px) { .nlw__inner { grid-template-columns: 5fr 7fr; align-items: start; } }
.nlw__eyebrow { margin: 0 0 8px; font-size: 14px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: var(--nlw-brand-dark); }
.nlw__title { margin: 0 0 12px; font-size: clamp(26px, 3.2vw, 36px); line-height: 1.15; font-weight: 800; }
.nlw__lead { margin: 0; font-size: 17px; line-height: 1.55; color: var(--nlw-muted); }

.nlw__form { display: grid; gap: 16px; }
.nlw__row { display: grid; gap: 16px; grid-template-columns: 1fr; }
@media (min-width: 560px) { .nlw__row { grid-template-columns: 1fr 1.4fr; } }
.nlw__label { display: block; margin-bottom: 6px; font-size: 15px; font-weight: 700; }
.nlw__opt { font-weight: 400; color: var(--nlw-muted); }
.nlw__input {
  width: 100%; min-height: 48px; padding: 10px 14px; font: inherit; font-size: 16px;
  color: var(--nlw-text); background: #fff; border: 2px solid var(--nlw-border); border-radius: 6px;
}
.nlw__input--short { max-width: 160px; }
.nlw__input:focus-visible, .nlw__chip:focus-within, .nlw__consent input:focus-visible, .nlw__btn:focus-visible {
  outline: 3px solid var(--nlw-brand); outline-offset: 2px;
}
.nlw__input[aria-invalid="true"] { border-color: var(--nlw-error); }
.nlw__help { margin: 6px 0 0; font-size: 14px; color: var(--nlw-muted); }
.nlw__error { margin: 6px 0 0; font-size: 14px; font-weight: 700; color: var(--nlw-error); }
.nlw__error::before { content: "⚠ "; }

.nlw__topics { border: 0; padding: 0; margin: 0; display: flex; flex-wrap: wrap; gap: 8px; }
.nlw__topics legend { padding: 0; margin-bottom: 8px; width: 100%; }
.nlw__chip {
  display: inline-flex; align-items: center; gap: 8px; min-height: 44px; padding: 8px 14px;
  border: 2px solid var(--nlw-border); border-radius: 999px; font-size: 15px; cursor: pointer; background: #fff;
}
.nlw__chip:has(input:checked) { border-color: var(--nlw-brand-dark); background: #EAF1FF; color: var(--nlw-brand-dark); font-weight: 700; }
.nlw__chip input { width: 20px; height: 20px; accent-color: var(--nlw-brand-dark); margin: 0; }

.nlw__consent { display: grid; grid-template-columns: 24px 1fr; gap: 10px; align-items: start; font-size: 14px; line-height: 1.5; }
.nlw__consent input { width: 22px; height: 22px; margin-top: 2px; accent-color: var(--nlw-brand-dark); }
.nlw__consent a { color: var(--nlw-brand-dark); font-weight: 700; text-decoration: underline; }

.nlw__btn {
  justify-self: start; min-height: 52px; padding: 12px 32px; font: inherit; font-size: 17px; font-weight: 800;
  color: #fff; background: var(--nlw-brand-dark); border: 0; border-radius: 6px; cursor: pointer;
  transition: background-color .15s ease;
}
.nlw__btn:hover { background: #123F94; }
.nlw__btn[aria-busy="true"] { opacity: 1; background: #123F94; cursor: progress; }
.nlw__btn[disabled] { cursor: not-allowed; }
.nlw__status:empty { display: none; }
.nlw__status { margin: 0; padding: 12px 16px; border-radius: 6px; font-weight: 700; }
.nlw__status--ok { background: var(--nlw-ok-bg); color: var(--nlw-ok); }
.nlw__status--err { background: #FCEBEA; color: var(--nlw-error); }

.nlw__hp { position: absolute; left: -10000px; width: 1px; height: 1px; overflow: hidden; }
@media (prefers-reduced-motion: reduce) { .nlw__btn { transition: none; } }
@media (prefers-color-scheme: dark) {
  .nlw { --nlw-bg: #1D1D1A; --nlw-text: #F5F5F3; --nlw-muted: #CFCFCB; --nlw-border: #6B6B67; --nlw-brand-dark: #8FB4FF; }
  .nlw__input, .nlw__chip { background: #2A2A27; color: var(--nlw-text); }
  .nlw__chip:has(input:checked) { background: #203A6B; color: #fff; }
  .nlw__btn { color: #1D1D1A; }           /* jasny przycisk, ciemny tekst: 9,5:1 */
  .nlw__btn:hover { background: #B8CFFF; }
}
```

```js
/* resources/js/newsletter-widget.js — progressive enhancement; bez JS działa zwykły POST */
(() => {
  const form = document.querySelector('[data-nlw-form]');
  if (!form) return;

  const email   = form.querySelector('#nlw-email');
  const emailEr = form.querySelector('#nlw-email-err');
  const consent = form.querySelector('input[name="consent"]');
  const consEr  = form.querySelector('#nlw-consent-err');
  const btn     = form.querySelector('[data-nlw-submit]');
  const label   = form.querySelector('[data-nlw-label]');
  const status  = form.querySelector('[data-nlw-status]');

  const showError = (el, msgEl, msg) => {
    msgEl.textContent = msg; msgEl.hidden = !msg;
    el.setAttribute('aria-invalid', msg ? 'true' : 'false');
  };

  const validate = () => {
    let ok = true;
    const v = email.value.trim();
    if (!v)                                  { showError(email, emailEr, 'Podaj adres e-mail.'); ok = false; }
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v)) { showError(email, emailEr, 'Ten adres wygląda na niepoprawny. Sprawdź, czy zawiera znak @ i domenę.'); ok = false; }
    else                                     showError(email, emailEr, '');
    if (!consent.checked)                    { showError(consent, consEr, 'Zaznacz zgodę, aby się zapisać.'); ok = false; }
    else                                     showError(consent, consEr, '');
    if (!ok) (v ? consent : email).focus();
    return ok;
  };

  email.addEventListener('blur', validate);

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    status.textContent = ''; status.className = 'nlw__status';
    if (!validate()) return;

    btn.disabled = true; btn.setAttribute('aria-busy', 'true'); label.textContent = 'Zapisuję…';

    try {
      const res = await fetch(form.action, {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: new FormData(form),
      });
      const data = await res.json().catch(() => ({}));

      if (res.ok) {
        // zawsze ten sam komunikat (brak enumeracji adresów)
        form.querySelectorAll('.nlw__row, .nlw__topics, .nlw__consent, .nlw__btn, .nlw__field').forEach(n => n.hidden = true);
        status.className = 'nlw__status nlw__status--ok';
        status.textContent = 'Dziękujemy! Sprawdź skrzynkę i kliknij link potwierdzający. Jeśli nie widzisz wiadomości, zajrzyj do spamu.';
        status.focus?.();
      } else if (res.status === 422 && data.errors) {
        const first = Object.values(data.errors)[0]?.[0] ?? 'Popraw dane w formularzu.';
        if (data.errors.email) showError(email, emailEr, data.errors.email[0]);
        status.className = 'nlw__status nlw__status--err'; status.textContent = first;
      } else if (res.status === 429) {
        status.className = 'nlw__status nlw__status--err'; status.textContent = 'Zbyt wiele prób. Spróbuj ponownie za minutę.';
      } else {
        throw new Error('server');
      }
    } catch {
      status.className = 'nlw__status nlw__status--err';
      status.textContent = 'Nie udało się wysłać formularza. Spróbuj ponownie lub napisz do nas: biuro@feer.org.pl';
    } finally {
      btn.disabled = false; btn.removeAttribute('aria-busy'); label.textContent = 'Zapisz się';
    }
  });
})();
```

**Endpoint widgetu** (`POST /newsletter/zapis`, `throttle:5,1`): `SpamGuard::inspect()` → walidacja (`email`, `topics[]` z `Subscriber::$availableTopics`, `consent` accepted) → `firstOrNew` po `email_hash` → zapis `newsletter_consents` (snapshot klauzuli) → kolejkowany `SendDoubleOptIn` → odpowiedź JSON `{ok:true}` lub redirect na `/subskrypcje/oczekiwanie` dla żądań bez JS. Logika przeniesiona z `SubscribeController::store()`; stary endpoint `/subskrypcje` zostaje jako alias.

---

## 5. Plan wdrożenia (iteracje)

| Iteracja | Zakres | Szacunek |
|---|---|---|
| **I1 — fundament** | migracje (§3), modele + `ChannelDriver` + `EmailChannel`, rozszerzenie `subscribers`, rejestr zgód, nowy widget + endpoint, strona preferencji, one-click unsubscribe, panel: subskrybenci/listy/import/eksport | 5–7 dni |
| **I2 — kampanie** | Mosaico + szablon `feer-1` (w tym blok `latestNews` i `ContentFeeder`), kreator 5 kroków, kolejka/batch/throttling, test, planowanie, ustawienia dostawców (Graph + SES/Mailgun) | 6–8 dni |
| **I3 — analityka** | piksel, przekierowania, webhooki bounce, raport kampanii, mapa cieplna, dashboard | 4–5 dni |
| **I4 — kanały dodatkowe** | `WebPushChannel` (spięcie z `push_subscriptions`), `SmsChannel`, segmenty dynamiczne, kampanie cykliczne (digest), A/B tematu, retencja/anonimizacja automatyczna, DPIA | 4–6 dni |

Testy: feature testy przepływu DOI i wypisu (w tym one-click POST), testy jobów z `Bus::fake()` / `Mail::fake()`, test `SegmentResolver` na SQLite, test webhooków z podpisem, audyt WCAG widgetu (axe + czytnik ekranu VoiceOver).


---

## 6. Co zostało zaimplementowane (stan na 2026-10-09)

| Obszar | Pliki / trasy | Uwagi |
|---|---|---|
| Moduł | `modules/Newsletter/` (`module.json`, `Providers/NewsletterServiceProvider`), włączany w panelu „Moduły” | `ModuleServiceProvider` przesuwa trasy modułów przed catch-all stron (`prioritizeModuleRoutes`) — bez tego `/newsletter` i `/wiem-feer` trafiały w `/{page:slug}` |
| Baza | `database/migrations/2026_10_11_100000_create_newsletter_module_tables.php` | rozszerzenie `subscribers` (uuid, `email_hash`, status, kanały, tagi, SZO/CRM), 14 tabel `newsletter_*`, kolumny `newsletter_*` w `site_settings`, `push_subscriptions.subscriber_id` |
| Modele | `App\Models\Subscriber` (statusy, `hashEmail`, `unsubscribe()`, `anonymize()`, `exportPersonalData()`), `Modules\Newsletter\Models\*` | |
| Publiczne | `/newsletter`, `/newsletter/{slug}`, `POST /newsletter/zapis`, `/n/potwierdz/{token}`, `/n/u/{token}` (GET + POST one-click, bez CSRF), `/n/p/{token}` (preferencje, eksport JSON, usunięcie, push), `/n/o/{uuid}.gif`, `/n/c/{uuid}/{hash}`, `/n/w/{uuid}`, `POST /n/webhook/{ses|mailgun|postmark|generic}` | stare `/subskrypcje/*` przekierowują |
| Panel | `/admin/newsletter` (pulpit), `kampanie` (kreator, edytor Mosaico, podgląd, test, wysyłka/plan/pauza/anuluj, raport + CSV), `subskrybenci` (lista, karta, edycja, import CSV/JSON, eksport, anonimizacja, akcje zbiorcze), `listy`, `segmenty` (budowniczy reguł z licznością na żywo), `szablony`, `formularze` (dedykowany formularz zapisu), `ustawienia` (dostawcy, limity, DOI, retencja, push/SMS, SZO/CRM, webhooki, kod zewnętrzny) | menu: Komunikacja → Newsletter |
| Edytor | Mosaico 0.18.11 zbudowany z npm (`public/vendor/mosaico/rs`, GPL-3.0 — `LICENSE`, `NOTICE.txt`), szablon `feer-1` (`public/vendor/mosaico/templates/feer-1`): kolory #1D1D1A/#1752BF/#F3F6FB, Montserrat, polskie etykiety, blok `latestNewsBlock`, tłumaczenie `rs/lang/mosaico-pl.json`; pasek z zapisem (autosave 90 s), ostrzeżenie o kontraście < 4,5:1, „Wstaw aktualność” (modal z wyszukiwarką, wstawia blok Artykuł) | backend obrazków: `MosaicoController` (upload do `storage/app/public/newsletter/images`, resize/cover/placeholder przez spatie/image + GD) |
| Wysyłka | `Jobs\DispatchCampaign` → `Bus::batch(SendDelivery…)` na kolejce `newsletter`, `RateLimited` per kanał (limit z ustawień), retry 60/300/1800 s, pauza/anulowanie, A/B tematu, kampanie cykliczne (`newsletter:recurring`) | mailer `newsletter` budowany z ustawień (Graph/SES/Mailgun/Postmark/SMTP) w providerze modułu |
| Kanały | `Channels\EmailChannel` (List-Unsubscribe + One-Click), `WebPushChannel` (istniejące `push_subscriptions`), `SmsChannel` (SMSAPI lub webhook; wymaga potwierdzonej zgody SMS) | |
| Analityka | piksel, przekierowania z UTM, słownik linków (także z treści dynamicznej), raport: KPI, oś 72 h, mapa cieplna nad podglądem, odbiorcy, kanały i klienci poczty, otwarcia proxy | |
| RODO | rejestr zgód `newsletter_consents` (snapshot klauzuli, IP-hash, UA, metoda), DOI z TTL, samoobsługowy eksport i usunięcie, anonimizacja + lista tłumienia 30 dni, retencja `newsletter:prune`, `activity_log` dla importu/eksportu/wysyłki | |
| Harmonogram | `newsletter:dispatch-due` (co minutę), `newsletter:recurring` (co godzinę), `newsletter:expire-pending`, `newsletter:prune`, `newsletter:sync-crm` | wymaga `schedule:run` i workera kolejki |

**Uruchomienie na serwerze:** `php84 artisan migrate --force`, worker `php84 artisan queue:work database --queue=newsletter,default --tries=3` pod supervisorem, w panelu: Newsletter → Ustawienia (nadawca, dostawca, limity) oraz Formularze zapisu (treści i klauzula). Opcjonalnie seed klauzul SZO i włączenie synchronizacji.

**Do zrobienia w kolejnych iteracjach:** Cloudflare Turnstile (pola są, brak weryfikacji po stronie serwera), parser DSN dla odbić z Microsoft Graph, wysyłka „o lokalnej godzinie odbiorcy” (flaga zapisana, dispatcher wysyła o czasie serwera), nocne liczenie `engagement_score`, testy feature.
