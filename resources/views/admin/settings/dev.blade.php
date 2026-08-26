@extends('admin.layout')

@section('title', 'SiteSettings — dokumentacja')

@section('content')
<div class="max-w-5xl space-y-10">

    {{-- ====== Nagłówek ====== --}}
    <div>
        <h1 class="text-xl font-bold text-ink">SiteSettings — dokumentacja dla programisty</h1>
        <p class="mt-1 text-sm text-muted">
            Opis tabeli <code class="rounded bg-gray-100 px-1 font-mono text-xs">site_settings</code>,
            stałych modelu i tras kontrolera.
            Dane aktualnej instancji: <code class="rounded bg-gray-100 px-1 font-mono text-xs">SiteSetting::current()</code> — zawsze jeden wiersz (ID&nbsp;={{ $settings->id }}).
        </p>
        <div class="mt-2 flex gap-3">
            <a href="{{ route('admin.ustawienia.edit') }}"
               class="rounded border border-gray-300 px-3 py-1 text-sm text-muted hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                ← Wróć do ustawień
            </a>
        </div>
    </div>

    {{-- ====== Trasy (routes) ====== --}}
    <section aria-labelledby="routes-heading">
        <h2 id="routes-heading" class="mb-3 text-base font-bold text-ink">Trasy (routes)</h2>
        <div class="overflow-x-auto rounded-lg border border-gray-200">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left font-bold text-ink">Metoda</th>
                        <th class="px-4 py-2 text-left font-bold text-ink">URI</th>
                        <th class="px-4 py-2 text-left font-bold text-ink">Nazwa trasy</th>
                        <th class="px-4 py-2 text-left font-bold text-ink">Akcja kontrolera</th>
                        <th class="px-4 py-2 text-left font-bold text-ink">Opis</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @php
                    $routes = [
                        ['GET',  'ustawienia',                'admin.ustawienia.edit',       'edit()',              'Formularz edycji ustawień (wszystkie zakładki)'],
                        ['PUT',  'ustawienia',                'admin.ustawienia.update',     'update()',            'Zapis ustawień z formularza'],
                        ['POST', 'ustawienia/test-poczty',    'admin.ustawienia.mail-test',  'mailTest()',          'Wysyłka testowej wiadomości e-mail'],
                        ['POST', 'ustawienia/strefa-nadpisz', 'admin.strefa.overwrite',      'overwriteStrefa()',   'Nadpisanie strony /strefa jako Strefa współpracownika'],
                        ['POST', 'ustawienia/prefix-panelu',  'admin.ustawienia.prefix',     'updateAdminPrefix()', 'Zmiana segmentu URL panelu (np. /admin → /cms)'],
                        ['GET',  'ustawienia/dev',            'admin.ustawienia.dev',        'dev()',               'Ta strona dokumentacji'],
                    ];
                    @endphp
                    @foreach ($routes as $r)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2">
                            <span class="rounded bg-blue-100 px-1.5 py-0.5 font-mono text-xs font-bold text-blue-800">{{ $r[0] }}</span>
                        </td>
                        <td class="px-4 py-2 font-mono text-xs text-ink">{{ config('app.admin_prefix', 'admin') }}/{{ $r[1] }}</td>
                        <td class="px-4 py-2 font-mono text-xs text-muted">{{ $r[2] }}</td>
                        <td class="px-4 py-2 font-mono text-xs text-muted">{{ $r[3] }}</td>
                        <td class="px-4 py-2 text-muted">{{ $r[4] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- ====== Kolumny tabeli ====== --}}
    <section aria-labelledby="columns-heading">
        <h2 id="columns-heading" class="mb-3 text-base font-bold text-ink">Kolumny tabeli <code class="font-mono text-base">site_settings</code></h2>
        <p class="mb-3 text-xs text-muted">
            Kolumna <code class="rounded bg-gray-100 px-1 font-mono">id</code> (bigint, PK) i znaczniki czasu (<code class="rounded bg-gray-100 px-1 font-mono">created_at</code>,
            <code class="rounded bg-gray-100 px-1 font-mono">updated_at</code>) pominięte. Typ PHP = typ po odczytaniu z modelu (cast).
        </p>
        <div class="overflow-x-auto rounded-lg border border-gray-200">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left font-bold text-ink">Kolumna (DB)</th>
                        <th class="px-4 py-2 text-left font-bold text-ink">Typ DB</th>
                        <th class="px-4 py-2 text-left font-bold text-ink">Cast PHP</th>
                        <th class="px-4 py-2 text-left font-bold text-ink">Opis</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 font-mono text-xs">
                    @php
                    $columns = [
                        // Tożsamość serwisu
                        ['site_name',          'text',     'string',   'Nazwa serwisu (widoczna w tytule zakładki i stopce)'],
                        ['tagline',            'text',     'string?',  'Krótkie hasło / podtytuł organizacji'],
                        ['site_url',           'text',     'string?',  'Kanoniczna URL serwisu (bez ukośnika na końcu)'],
                        ['meta_description',   'text',     'string?',  'Domyślny opis meta dla SEO'],
                        ['allow_indexing',     'boolean',  'bool',     'Czy zezwalać robotom (true = indeksuj, false = noindex)'],
                        ['ga_measurement_id',  'text',     'string?',  'ID Google Analytics 4 (G-XXXXXXXX)'],
                        // Wygląd i kolory
                        ['brand_color',        'text',     'string',   'Główny kolor marki (#hex, kontrast WCAG AA gwarantowany)'],
                        ['brand_color_2',      'text',     'string?',  'Drugi kolor marki (opcjonalny)'],
                        ['brand_color_3',      'text',     'string?',  'Trzeci kolor marki (opcjonalny)'],
                        ['brand_color_4',      'text',     'string?',  'Czwarty kolor marki (opcjonalny)'],
                        ['ngo_color',          'text',     'string?',  'Kolor NGO używany na stronie /wsparcie'],
                        ['sub_brands',         'text/json','array?',   'Podmarka: [{name, color}] — widgety i stopka'],
                        ['header_layout',      'text',     'string?',  'Układ nagłówka: standard | wide_mission'],
                        ['logo_only',          'boolean',  'bool',     'Pokaż tylko logo (bez nazwy serwisu) w nagłówku'],
                        ['logo_alt',           'text',     'string?',  'Alt-text logo (domyślnie: site_name)'],
                        ['show_topbar_bip',    'boolean',  'bool',     'Pokaż link BIP w górnym pasku'],
                        ['show_topbar_social', 'boolean',  'bool',     'Pokaż ikony sociali w górnym pasku'],
                        ['show_cms_credit',    'boolean',  'bool',     'Pokaż stopkę "Powered by FEER CMS"'],
                        ['news_layout',        'text',     'string?',  'Układ listy aktualności: grid | list | cards'],
                        ['volunteer_layout',   'text',     'string?',  'Układ listy wolontariatu: grid | list'],
                        // Strona główna
                        ['homepage_section_order', 'text/json', 'array?', 'Kolejność sekcji strony głównej (tablica kluczy)'],
                        ['events_home_color',  'text',     'string?',  'Kolor akcentu sekcji wydarzeń na stronie głównej'],
                        ['homepage_banner_text',          'text', 'string?', 'Tekst bannera powitalnego'],
                        ['homepage_banner_link_label',    'text', 'string?', 'Etykieta linku bannera'],
                        ['homepage_banner_link_url',      'text', 'string?', 'URL linku bannera'],
                        ['homepage_banner_visible_from',  'datetime', 'datetime?', 'Data/godzina początku widoczności bannera'],
                        ['homepage_banner_visible_until', 'datetime', 'datetime?', 'Data/godzina końca widoczności bannera'],
                        // Moduły
                        ['disabled_modules',   'text/json','array?',   'Lista wyłączonych modułów (klucze z MODULES)'],
                        // Social media
                        ['facebook_url',  'text', 'string?', 'URL profilu Facebook'],
                        ['twitter_url',   'text', 'string?', 'URL profilu X (Twitter)'],
                        ['instagram_url', 'text', 'string?', 'URL profilu Instagram'],
                        ['linkedin_url',  'text', 'string?', 'URL profilu LinkedIn'],
                        ['youtube_url',   'text', 'string?', 'URL kanału YouTube'],
                        ['substack_url',  'text', 'string?', 'URL Substack (newsletter)'],
                        // BIP
                        ['bip_url',   'text', 'string?', 'URL zewnętrznego BIP'],
                        ['bip_intro', 'text', 'string?', 'Tekst wprowadzający do BIP na stronie'],
                        // Kontakt
                        ['contact_address',     'text', 'string?', 'Ulica i numer w stopce / na stronie kontakt'],
                        ['contact_city',        'text', 'string?', 'Miasto (kod + nazwa)'],
                        ['contact_email',       'text', 'string?', 'Główny e-mail kontaktowy'],
                        ['contact_phone',       'text', 'string?', 'Numer telefonu'],
                        ['contact_intro',       'text', 'string?', 'Wstęp na stronie Kontakt'],
                        ['contact_bank_accounts','text/json','array?','Rachunki bankowe [{label, number}]'],
                        ['contact_edelivery_address','text','string?','Adres eDorêczenia (e-Doręczenia)'],
                        ['contact_schedule',    'text/json','array?', 'Harmonogram spotkań [{title,weekday,date,time,where,note}]'],
                        ['contact_schedule_enabled','boolean','bool',  'Czy harmonogram jest aktywny'],
                        ['contact_schedule_title','text','string?',   'Tytuł sekcji harmonogramu'],
                        ['contact_no_schedule_note','text','string?', 'Komunikat gdy brak terminów'],
                        ['contact_remote_note','text','string?',      'Informacja o spotkaniach online'],
                        ['contact_meeting_notify_email','text','string?','E-mail powiadamiany o nowych zgłoszeniach spotkań'],
                        ['contact_meeting_title','text','string?',    'Tytuł sekcji zgłoszeń spotkań'],
                        ['contact_online_meeting_url',  'text','string?','URL spotkania online (Zoom/Teams)'],
                        ['contact_online_meeting_label','text','string?','Etykieta przycisku spotkania online'],
                        ['contact_online_meeting_text', 'text','string?','Tekst obok przycisku spotkania online'],
                        ['contact_shipping_note',    'text','string?','Nota przy adresie paczkomat'],
                        ['contact_paczkomat_code',   'text','string?','Kod paczkomatu (np. WAW45M)'],
                        ['contact_paczkomat_address','text','string?','Adres paczkomatu'],
                        ['contact_paczkomat_location','text','string?','Miejsce paczkomatu (np. „hol główny")'],
                        ['contact_shipping_phone',   'text','string?','Telefon kurierski'],
                        ['contact_shipping_visible', 'boolean','bool','Czy pokazywać sekcję wysyłki'],
                        ['contact_box_text',          'text','string?','Treść ramki kontaktowej (Markdown)'],
                        ['contact_box_link_label',    'text','string?','Etykieta linku w ramce kontaktowej'],
                        ['contact_box_link_url',      'text','string?','URL linku w ramce kontaktowej'],
                        ['contact_box_visible_from',  'datetime','datetime?','Ramka widoczna od'],
                        ['contact_box_visible_until', 'datetime','datetime?','Ramka widoczna do'],
                        // Logowanie / bezpieczeństwo
                        ['microsoft_login_enabled', 'boolean','bool',     'Logowanie przez Microsoft 365 (OAuth)'],
                        ['microsoft_client_id',     'text','string?',     'Azure App: client_id'],
                        ['microsoft_client_secret', 'text','encrypted?',  'Azure App: client_secret (zaszyfrowany)'],
                        ['microsoft_tenant_id',     'text','string?',     'Azure App: tenant_id lub "common"'],
                        ['member_login_enabled',    'boolean','bool',     'Logowanie dla członków przez MS365'],
                        ['member_allowed_domains',  'text','string?',     'Dozwolone domeny e-mail (CSV)'],
                        ['szo_api_url',             'text','string?',     'URL API SZO (system zarządzania organizacją)'],
                        ['yubico_client_id',        'text','string?',     'Klucz Yubico: client_id (weryfikacja YubiKey)'],
                        ['yubico_secret_key',       'text','encrypted?',  'Klucz Yubico: secret_key (zaszyfrowany)'],
                        ['two_factor_required_admins','boolean','bool',   'Wymuś 2FA dla administratorów'],
                        ['cookie_banner_enabled',   'boolean','bool',     'Czy pokazywać baner cookies'],
                        ['cookie_banner_text',      'text','string?',     'Treść banera cookies'],
                        ['maintenance_mode',        'boolean','bool',     'Tryb przerwy technicznej'],
                        ['maintenance_message',     'text','string?',     'Komunikat wyświetlany podczas przerwy'],
                        // Edytor treści
                        ['content_editor',          'text','string?',     'Edytor: tinymce | markdown'],
                        // Newsleter/hero
                        ['newsletter_code',         'text','string?',     'Kod embed newslettera (np. Mailchimp)'],
                        // Poczta SMTP
                        ['mail_transport',          'text','string?',     'Dostawca poczty: default | smtp | sendmail | log'],
                        ['mail_from_address',       'text','string?',     'Adres "From" w wysyłanych e-mailach'],
                        ['mail_from_name',          'text','string?',     'Nazwa "From" w wysyłanych e-mailach'],
                        ['mail_host',               'text','string?',     'Serwer SMTP'],
                        ['mail_port',               'integer','int?',     'Port SMTP'],
                        ['mail_username',            'text','string?',    'Login SMTP'],
                        ['mail_password',           'text','encrypted?',  'Hasło SMTP (zaszyfrowane)'],
                        ['mail_encryption',         'text','string?',     'Szyfrowanie: tls | ssl | null'],
                        // Dane rejestrowe
                        ['krs_number',    'text','string?', 'Numer KRS'],
                        ['nip_number',    'text','string?', 'Numer NIP'],
                        ['regon_number',  'text','string?', 'Numer REGON'],
                        ['bank_account_number',     'text','string?', 'Główny numer konta bankowego'],
                        ['bank_account_tax_number', 'text','string?', 'Numer konta do wpłat 1,5%'],
                        // Koordynatorzy
                        ['show_coordinators', 'boolean','bool', 'Pokaż sekcję koordynatorów na stronie O nas'],
                        // Wsparcie / /wsparcie
                        ['support_intro',                'text','string?', 'Wstęp sekcji wsparcia'],
                        ['support_quick_transfer_url',   'text','string?', 'URL szybkiego przelewu'],
                        ['support_buycoffee_url',        'text','string?', 'URL buycoffee.to'],
                        ['support_wplacam_url',          'text','string?', 'URL wplacam.pl'],
                        ['support_show_partners',        'boolean','bool', 'Pokaż partnerów / darczyńców'],
                        ['support_testimonial_quote',    'text','string?', 'Cytat darczyńcy'],
                        ['support_testimonial_author',   'text','string?', 'Imię darczyńcy'],
                        ['support_testimonial_role',     'text','string?', 'Rola/tytuł darczyńcy'],
                        ['support_fundraiser_title',     'text','string?', 'Tytuł zbiórki'],
                        ['support_fundraiser_text',      'text','string?', 'Opis zbiórki'],
                        ['support_fundraiser_goal',      'integer','int?', 'Cel zbiórki (w groszach lub złotych)'],
                        ['support_fundraiser_raised',    'integer','int?', 'Zebrana kwota'],
                        ['support_fundraiser_url',       'text','string?', 'Link do zewnętrznej platformy zbiórki'],
                        ['support_fundraiser_cta_label', 'text','string?', 'Etykieta przycisku zbiórki'],
                        // Hero strony wsparcia
                        ['support_hero_badge',           'text','string?', 'Etykieta (badge) sekcji hero'],
                        ['support_hero_title',           'text','string?', 'Tytuł sekcji hero'],
                        ['support_hero_subtitle',        'text','string?', 'Podtytuł sekcji hero'],
                        ['support_hero_cta_label',       'text','string?', 'Etykieta CTA sekcji hero'],
                        ['support_benefits_title',       'text','string?', 'Tytuł sekcji korzyści'],
                        ['support_benefits_subtitle',    'text','string?', 'Podtytuł sekcji korzyści'],
                        ['support_benefit1_icon',        'text','string?', 'FontAwesome klasa ikony korzyści 1'],
                        ['support_benefit1_title',       'text','string?', 'Tytuł korzyści 1'],
                        ['support_benefit1_text',        'text','string?', 'Opis korzyści 1'],
                        ['support_benefit2_icon',        'text','string?', 'FontAwesome klasa ikony korzyści 2'],
                        ['support_benefit2_title',       'text','string?', 'Tytuł korzyści 2'],
                        ['support_benefit2_text',        'text','string?', 'Opis korzyści 2'],
                        ['support_benefit3_icon',        'text','string?', 'FontAwesome klasa ikony korzyści 3'],
                        ['support_benefit3_title',       'text','string?', 'Tytuł korzyści 3'],
                        ['support_benefit3_text',        'text','string?', 'Opis korzyści 3'],
                        ['support_methods_title',        'text','string?', 'Tytuł sekcji metod wpłat'],
                        ['support_method1_title',        'text','string?', 'Tytuł metody wpłaty 1 (przelew)'],
                        ['support_method1_account_label','text','string?', 'Etykieta pola konta (metoda 1)'],
                        ['support_method1_tax_label',    'text','string?', 'Etykieta pola 1,5% (metoda 1)'],
                        ['support_transfer_title',       'text','string?', 'Nagłówek sekcji transferu'],
                        ['support_method1_transfer_label','text','string?','Etykieta pola transferu'],
                        ['support_method2_title',        'text','string?', 'Tytuł metody wpłaty 2 (buycoffee)'],
                        ['support_method2_text',         'text','string?', 'Opis metody 2'],
                        ['support_method2_cta_label',    'text','string?', 'CTA metody 2'],
                        ['support_method3_title',        'text','string?', 'Tytuł metody wpłaty 3'],
                        ['support_method3_text',         'text','string?', 'Opis metody 3'],
                        ['support_method3_cta_label',    'text','string?', 'CTA metody 3'],
                        ['support_method4_title',        'text','string?', 'Tytuł metody wpłaty 4 (niestandardowa)'],
                        ['support_method4_text',         'text','string?', 'Opis metody 4'],
                        ['support_method4_cta_label',    'text','string?', 'CTA metody 4'],
                        ['support_outro_title',          'text','string?', 'Tytuł zakończenia strony wsparcia'],
                        ['support_outro_subtitle',       'text','string?', 'Podtytuł zakończenia'],
                        // Dostępność
                        ['accessibility_entity_name',       'text','string?', 'Oficjalna nazwa podmiotu w deklaracji dostępności'],
                        ['accessibility_status',            'text','string?', 'Status: fully | partial | non'],
                        ['accessibility_status_note',       'text','string?', 'Szczegóły statusu dostępności (Markdown)'],
                        ['accessibility_page_published_at', 'date','date?',   'Data opublikowania deklaracji'],
                        ['accessibility_page_updated_at',   'date','date?',   'Data ostatniej aktualizacji deklaracji'],
                        ['accessibility_declaration_date',  'date','date?',   'Data sporządzenia deklaracji'],
                        ['accessibility_review_method',     'text','string?', 'Metoda przeglądu: self | external | user'],
                        ['accessibility_contact_name',      'text','string?', 'Imię i nazwisko koordynatora ds. dostępności'],
                        ['accessibility_contact_email',     'text','string?', 'E-mail koordynatora dostępności'],
                        ['accessibility_contact_phone',     'text','string?', 'Telefon koordynatora dostępności'],
                        ['accessibility_architectural',     'text','string?', 'Opis dostępności architektonicznej (Markdown)'],
                        // Materiały i projekty
                        ['projects_intro',   'text','string?', 'Wstęp na stronie Projekty'],
                        ['materials_intro',  'text','string?', 'Wstęp na stronie Materiały'],
                        ['materials_notice', 'text','string?', 'Komunikat na liście materiałów'],
                        // Unsplash
                        ['unsplash_access_key', 'text','encrypted?', 'Klucz API Unsplash (zaszyfrowany)'],
                        // Wide Mission layout
                        ['wide_mission_social_1',          'text','string?', 'Pierwsza ikona social w WM nagłówku'],
                        ['wide_mission_social_2',          'text','string?', 'Druga ikona social w WM nagłówku'],
                        ['wide_mission_social_3',          'text','string?', 'Trzecia ikona social w WM nagłówku'],
                        ['infobar_show_date',              'bool','bool',    'Pasek informacyjny: data'],
                        ['infobar_show_nameday',           'bool','bool',    'Pasek informacyjny: imieniny'],
                        ['office_show_account',            'bool','bool',    'Substyl urzędowy: nr konta na środku belki'],
                        ['office_show_search',             'bool','bool',    'Substyl urzędowy: wyszukiwarka z boku'],
                        ['contact_layout',                 'text','string',  'Wariant strony kontaktowej: classic/split'],
                        ['contact_office_address',         'text','string?', 'Adres biura / do korespondencji'],
                        ['contact_office_city',            'text','string?', 'Biuro: kod pocztowy i miasto'],
                        ['contact_office_building',        'text','string?', 'Biuro: nazwa budynku (np. Biurowiec HEXAGON)'],
                        ['contact_office_note',            'text','string?', 'Biuro: wskazówka dojścia (z kim dzielimy biuro)'],
                        ['contact_office_photo_alt',       'text','string?', 'Biuro: opis alternatywny zdjęcia'],
                        ['wide_mission_cta_label',         'text','string?', 'CTA w nagłówku Wide Mission'],
                        ['wide_mission_cta_url',           'text','string?', 'URL CTA w nagłówku WM'],
                        ['wide_mission_show_mission',      'boolean','bool', 'Pokaż sekcję misji w nagłówku WM'],
                        ['wide_mission_highlight_account', 'boolean','bool', 'Wyróżnij konto MS365 w nagłówku WM'],
                        ['wide_mission_nav_align',         'text','string?', 'Wyrównanie nav: left | center'],
                        ['wide_mission_search_in_nav',     'boolean','bool', 'Pole wyszukiwania w nav WM'],
                        ['wide_mission_sidebar',           'boolean','bool', 'Boczny pasek w szablonie WM'],
                        ['wide_mission_sidebar_style',     'text','string?', 'Styl sidebara: colored | cards'],
                        ['wide_mission_nav_style',         'text','string?', 'Styl nav: brand_bar | icons_white'],
                    ];
                    @endphp
                    @foreach ($columns as $col)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-1.5 text-ink">{{ $col[0] }}</td>
                        <td class="px-4 py-1.5 text-muted">{{ $col[1] }}</td>
                        <td class="px-4 py-1.5">
                            <span class="rounded px-1 py-0.5
                                @if(str_contains($col[2], 'bool')) bg-yellow-100 text-yellow-800
                                @elseif(str_contains($col[2], 'array')) bg-purple-100 text-purple-800
                                @elseif(str_contains($col[2], 'encrypted')) bg-red-100 text-red-800
                                @elseif(str_contains($col[2], 'datetime') || str_contains($col[2], 'date')) bg-blue-100 text-blue-800
                                @elseif(str_contains($col[2], 'int')) bg-green-100 text-green-800
                                @else bg-gray-100 text-gray-700
                                @endif">{{ $col[2] }}</span>
                        </td>
                        <td class="px-4 py-1.5 font-sans text-muted">{{ $col[3] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- ====== Stałe modelu ====== --}}
    <section aria-labelledby="consts-heading">
        <h2 id="consts-heading" class="mb-3 text-base font-bold text-ink">Stałe modelu <code class="font-mono text-base">SiteSetting</code></h2>
        <div class="space-y-6">

            @php
            $consts = [
                ['MODULES', \App\Models\SiteSetting::MODULES, 'Dostępne moduły CMS (klucz → etykieta). Klucz trafia do disabled_modules.'],
                ['HOMEPAGE_SECTIONS', \App\Models\SiteSetting::HOMEPAGE_SECTIONS, 'Sekcje strony głównej (klucz → etykieta). Kolejność w homepage_section_order.'],
                ['HEADER_LAYOUTS', \App\Models\SiteSetting::HEADER_LAYOUTS, 'Dostępne układy nagłówka (klucz → etykieta).'],
                ['EDITORS', \App\Models\SiteSetting::EDITORS, 'Dostępne edytory treści.'],
                ['AUDIENCES', \App\Models\SiteSetting::AUDIENCES, 'Grupy docelowe treści (news, podstrony).'],
                ['MAIL_TRANSPORTS', \App\Models\SiteSetting::MAIL_TRANSPORTS, 'Dostępne dostawcy e-mail.'],
                ['MAIL_ENCRYPTIONS', \App\Models\SiteSetting::MAIL_ENCRYPTIONS, 'Dostępne typy szyfrowania SMTP.'],
                ['SOCIAL_KEYS', \App\Models\SiteSetting::SOCIAL_KEYS, 'Dostępne klucze portali social media.'],
                ['ACCESSIBILITY_STATUSES', \App\Models\SiteSetting::ACCESSIBILITY_STATUSES, 'Statusy deklaracji dostępności (WCAG).'],
                ['ACCESSIBILITY_REVIEW_METHODS', \App\Models\SiteSetting::ACCESSIBILITY_REVIEW_METHODS, 'Metody przeglądu dostępności.'],
            ];
            @endphp

            @foreach ($consts as [$name, $values, $desc])
            <div class="rounded-lg border border-gray-200 bg-white">
                <div class="flex items-start justify-between border-b border-gray-100 px-4 py-3">
                    <div>
                        <code class="font-mono text-sm font-bold text-ink">SiteSetting::{{ $name }}</code>
                        <p class="mt-0.5 text-xs text-muted">{{ $desc }}</p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full font-mono text-xs">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left font-bold text-ink">Klucz</th>
                                <th class="px-4 py-2 text-left font-bold text-ink">Etykieta</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($values as $key => $label)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-1.5 text-brand">{{ $key }}</td>
                                <td class="px-4 py-1.5 font-sans text-muted">{{ $label }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endforeach
        </div>
    </section>

    {{-- ====== Metody publiczne ====== --}}
    <section aria-labelledby="methods-heading">
        <h2 id="methods-heading" class="mb-3 text-base font-bold text-ink">Publiczne metody modelu</h2>
        <div class="overflow-x-auto rounded-lg border border-gray-200">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left font-bold text-ink">Metoda</th>
                        <th class="px-4 py-2 text-left font-bold text-ink">Zwraca</th>
                        <th class="px-4 py-2 text-left font-bold text-ink">Opis</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 font-mono text-xs">
                    @php
                    $methods = [
                        ['current()',                  'static → SiteSetting', 'Jedyny wiersz (tworzy jeśli brak). Buforowany w request.'],
                        ['orderedHomepageSections()',  'array<string>',         'Klucze sekcji strony głównej w zapisanej kolejności.'],
                        ['logoUrl()',                  'string|null',           'URL logo (Spatie Media Library, kolekcja "logo").'],
                        ['ogImageUrl()',               'string|null',           'URL obrazu OG (kolekcja "og_image").'],
                        ['supportImageUrl()',          'string|null',           'URL zdjęcia na stronie wsparcia.'],
                        ['bipLogoUrl()',               'string|null',           'URL logo BIP.'],
                        ['newsDefaultImageUrl()',      'string|null',           'URL domyślnego zdjęcia aktualności.'],
                        ['brandColorN(int $n)',        'string',                'Kolor marki o numerze n (1–4). Fallback: brand_color.'],
                        ['brandPaletteColors()',       'array<string>',         'Tablica unikalnych kolorów palety marki.'],
                        ['hasExtraBrandColors()',      'bool',                  'Czy zdefiniowano dodatkowe kolory marki (2–4).'],
                        ['brandColorDark()',           'string',                'Przyciemnienie brand_color (dla hover).'],
                        ['brandColorLight()',          'string',                'Rozjaśnienie brand_color (dla tła).'],
                        ['brandPalette(?string $hex)', 'array{bg,text,border,...}', 'Paleta CSS dla podanego koloru (lub brand_color).'],
                        ['eventsHomeAccent()',         'string',                'Kolor akcentu wydarzeń lub brand_color jako fallback.'],
                        ['contrastSafeColor(string)','string',                 'Przyciemnia kolor do min. kontrastu WCAG AA 4.5:1 na białym.'],
                        ['audienceOptions()',          'array',                 'Dostępne wartości pola audience dla danej konfiguracji.'],
                        ['isModuleEnabled(string)',   'bool',                  'Czy moduł o danym kluczu jest aktywny.'],
                        ['hasRegistryData()',         'bool',                  'Czy podano jakikolwiek numer rejestrowy (KRS/NIP/REGON).'],
                        ['logoAltText()',             'string',                'Alt dla logo (logo_alt lub site_name).'],
                        ['showLogoOnly()',            'bool',                  'Czy ukryć nazwę serwisu obok logo.'],
                        ['cookieBannerText()',        'string',                'Tekst baneru cookies (lub domyślny).'],
                        ['maintenanceMessage()',      'string',                'Komunikat przerwy (lub DEFAULT_MAINTENANCE_MESSAGE).'],
                        ['hasFundraiser()',           'bool',                  'Czy skonfigurowano zbiórkę na stronie wsparcia.'],
                        ['fundraiserProgress()',      'int (0–100)',            'Procent realizacji celu zbiórki.'],
                        ['unsplashAccessKey()',       '?string',               'Odszyfrowany klucz Unsplash API.'],
                        ['mailConfigured()',          'bool',                  'Czy podano niestandardową konfigurację SMTP.'],
                        ['microsoftConfig()',         'array',                 'Klucze konfiguracji OAuth Microsoft dla adminów.'],
                        ['microsoftLoginEnabled()',   'bool',                  'Czy logowanie MS365 jest włączone i skonfigurowane.'],
                        ['memberMicrosoftConfig()',   'array',                 'Klucze konfiguracji OAuth MS365 dla członków.'],
                        ['memberLoginEnabled()',      'bool',                  'Czy logowanie dla członków jest aktywne.'],
                        ['memberAllowedDomains()',    'array<string>',         'Lista dozwolonych domen e-mail dla członków.'],
                        ['memberEmailAllowed(?string)', 'bool',               'Czy podany e-mail pasuje do dozwolonych domen.'],
                        ['szoConfigured()',           'bool',                  'Czy API SZO jest skonfigurowane.'],
                        ['szoKomunikatyUrl()',        '?string',               'URL API SZO komunikatów.'],
                        ['szoPanelUrl()',             '?string',               'URL panelu SZO.'],
                        ['szoTozsamoscUrl()',         '?string',               'URL API SZO tożsamości.'],
                        ['yubicoConfigured()',        'bool',                  'Czy YubiKey jest skonfigurowany.'],
                        ['accessibilityEntityName()','string',                 'Nazwa podmiotu do deklaracji dostępności.'],
                        ['accessibilityContactEmail()','?string',             'E-mail koordynatora dostępności.'],
                        ['accessibilityContactPhone()','?string',             'Telefon koordynatora dostępności.'],
                        ['accessibilityStatusLabel()','string',               'Czytelna etykieta statusu dostępności.'],
                        ['contactScheduleUpcoming()','array',                 'Nadchodzące terminy spotkań z harmonogramu.'],
                        ['meetingNotifyEmail()',      '?string',               'E-mail powiadamiany o zgłoszeniach spotkań.'],
                        ['contactBoxIsVisible()',     'bool',                  'Czy ramka kontaktowa jest teraz aktywna (wg dat).'],
                        ['homepageBannerIsVisible()', 'bool',                 'Czy baner strony głównej jest teraz aktywny.'],
                        ['supportGalleryImages()',   'Collection',            'Kolekcja mediów galerii wsparcia (Spatie Media).'],
                        ['supportText(string $key)', 'string',                'Pole wsparcia z fallbackiem do SUPPORT_DEFAULTS.'],
                        ['registerMediaCollections()','void',                 'Kolekcje Spatie: logo, og_image, support_image, support_gallery, news_default_image, bip_logo.'],
                    ];
                    @endphp
                    @foreach ($methods as [$sig, $returns, $desc])
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-1.5 text-brand">{{ $sig }}</td>
                        <td class="px-4 py-1.5 text-muted">{{ $returns }}</td>
                        <td class="px-4 py-1.5 font-sans text-muted">{{ $desc }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- ====== Aktualne wartości ====== --}}
    <section aria-labelledby="live-heading">
        <h2 id="live-heading" class="mb-3 text-base font-bold text-ink">Aktualne wartości <span class="text-sm font-normal text-muted">(SiteSetting::current())</span></h2>
        <div class="overflow-x-auto rounded-lg border border-gray-200 bg-gray-50 p-4">
            <pre class="whitespace-pre-wrap break-all font-mono text-xs text-ink">{{ json_encode($settings->only($settings->getFillable()), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
    </section>

    {{-- ====== Ręczne powiadomienie push ====== --}}
    <section aria-labelledby="push-heading">
        <h2 id="push-heading" class="mb-1 text-base font-bold text-ink">Ręczne powiadomienie push</h2>
        <p class="mb-3 text-sm text-muted">
            Wyślij powiadomienie push do wszystkich aktywnych subskrybentów.
            Subskrypcji w bazie: <strong>{{ \App\Models\PushSubscription::count() }}</strong>.
        </p>

        @if (session('status'))
            <div class="mb-4 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-800 ring-1 ring-green-200" role="alert">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.push.send') }}" class="max-w-lg space-y-4 rounded-xl border border-gray-200 bg-white p-5">
            @csrf
            <div>
                <label for="push_title" class="mb-1 block text-sm font-medium text-ink">Tytuł <span aria-hidden="true">*</span></label>
                <input id="push_title" name="push_title" type="text" maxlength="80" required
                       value="{{ old('push_title') }}"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand"
                       aria-describedby="push_title_hint">
                <p id="push_title_hint" class="mt-0.5 text-xs text-muted">Maks. 80 znaków.</p>
                @error('push_title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="push_body" class="mb-1 block text-sm font-medium text-ink">Treść <span aria-hidden="true">*</span></label>
                <textarea id="push_body" name="push_body" maxlength="200" required rows="3"
                          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand"
                          aria-describedby="push_body_hint">{{ old('push_body') }}</textarea>
                <p id="push_body_hint" class="mt-0.5 text-xs text-muted">Maks. 200 znaków.</p>
                @error('push_body') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="push_url" class="mb-1 block text-sm font-medium text-ink">URL (opcjonalny)</label>
                <input id="push_url" name="push_url" type="url" maxlength="500"
                       value="{{ old('push_url', '/') }}"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand"
                       aria-describedby="push_url_hint">
                <p id="push_url_hint" class="mt-0.5 text-xs text-muted">Adres, który otworzy się po kliknięciu powiadomienia.</p>
                @error('push_url') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <button type="submit"
                    class="rounded-lg bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                Wyślij powiadomienie
            </button>
        </form>
    </section>

</div>
@endsection
