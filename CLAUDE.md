# feer-web: kontekst projektu

Pełna historia zmian jest w Obsidianie (`Projekty/Web/FEER Website – Dziennik zmian.md`). Tu trzymam ostatnie zmiany, żeby nie trzeba było za każdym razem otwierać vaulta. Czytaj tę sekcję na początku pracy.

## Zasada utrzymania

Po każdej ukończonej zmianie dopisz ją na górę sekcji „Ostatnie zmiany” (data, jedna linia: co i gdzie). Zostaw maksymalnie ~15 wpisów, starsze usuń (pełna historia i tak jest w Obsidianie). Wpis do Obsidiana robisz dodatkowo.

## Ostatnie zmiany

- 2026-10-31: panel na subwitrynach: `ResolveAdminActiveSite` działa przed `SubstituteBindings` (priorytet w `bootstrap/app.php`) i przełącza na witrynę rekordu z adresu zamiast 404; pasek „Edytujesz: <witryna>” w layoucie panelu (`admin/partials/active-site-bar`); testy.
- 2026-10-31: ekran wyboru witryny po logowaniu (`/panel/wybor-witryny`, raz na sesję); „Przenieś ustawienia” między witrynami (nagłówek, kolory, moduły; `SiteChooserController`); „Przenieś ustawienia na podstrony” w edytorze strony; „Kopiuj do witryny” także w edytorach stron, aktualności, wydarzeń i partnerów; fix „Klonuj” wydarzenia.
- 2026-10-31: fix „Klonuj” wydarzenia (500: `starts_at` jest NOT NULL; klon zachowuje termin oryginału); test.
- 2026-10-31: „Kopiuj do witryny” także dla partnerów (z logo), aktualności (szkic, zdjęcie, tagi) i wydarzeń (szkic, FAQ); wspólny trait `CopiesToSite` i partial `admin/partials/copy-to-site`; seeder szkiców stron dla instytucji w witrynie /szkolenia (`SzkoleniaInstitutionPagesSeeder`); testy.
- 2026-10-31: fix 500 w witrynie z prefiksem ścieżki (`/szkolenia`): `site_route()` wraca do zwykłej trasy, gdy nie ma wariantu `site.<nazwa>` (np. `materials.index`); test `SiteRouteFallbackTest`.
- 2026-10-31: uniwersalna rama `<x-proj-frame>` (płaska jasnoszara jak wiersze listy działań) na stronie działania; Beneficjent = „Ty lub osoba, która korzysta z działania”; seeder przepisujący stronę o odpłatnej działalności.
- 2026-10-31: moduł „Jak wesprzeć w trzech krokach” na /wsparcie (edytowalny w Ustawienia → Wsparcie → Treść, przełącznik ukrycia, migracja); sekcja „na co idą darowizny” przed „jak pomóc”.
- 2026-10-31: szablony strony działania w panelu (klasyczny, panel po lewej, trzy kolumny; kolumna `layout_template`, migracja); układ „najważniejszych informacji” jako kafelki.
- 2026-10-31: antyspam (honeypot, żeton, zadanie tekstowe) w zapisie na materiały i rejestracji na landing page; opis zadania antyspamowego w „Zadaj pytanie”.
- 2026-10-31: moduł panelu „Jakość tekstów” (`/panel/jakosc-tekstow`): skaner wzorców do poprawy w treściach, tylko odczyt.
- 2026-10-31: CRM: skrypt `scripts/szo/create-contact-form.php` (formularz „kontakt” w SZO) i `ContactFormSzoLinkSeeder`; „ponów nieudane” w kampaniach pod `newsletter.send`.
- 2026-10-31: pliki: rodzaj obok tytułu, pobieranie pojedynczego pliku w menedżerze, płaska strona RODO, przełącznik „Lista plików na stronie”.
- 2026-10-30: seeder poprawionych tekstów podstron weCMS i tytułów działań weCMS / „Dostępność w praktyce”; testy.
- 2026-10-30: podpis „Misja Fundacji” nad hasłem organizacji w O nas (etykieta edytowalna: `about_labels.motto_label`).
- 2026-10-30: poprawione teksty domyślne „Dołącz do nas” i „Wesprzyj nas”, wstęp wolontariatu, seeder ofert wolontariatu.
