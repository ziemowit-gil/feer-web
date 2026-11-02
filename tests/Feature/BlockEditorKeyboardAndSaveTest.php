<?php

namespace Tests\Feature;

use App\Support\Color;
use Tests\TestCase;

/**
 * Edytor blokowy: zapis (zmiany w trakcie zapisu, ustawienia panelu bocznego, komunikat o braku sieci), fokus klawiatury
 * po przerysowaniach (cofnij/ponów, zamykanie paneli, pasek bloku, panel boczny), nazwa przycisku dostępności na wąskich
 * ekranach, kontrast przycisku zapisu i ruch przy prefers-reduced-motion.
 *
 * JS i CSS sprawdzamy statycznie, ale pilnujemy KOLEJNOŚCI w ciałach konkretnych funkcji (np. zapamiętanie fokusu przed
 * przerysowaniem), a nie samej obecności napisów — dzięki temu cofnięcie poprawki przez przesunięcie wiersza też wywala test.
 * Samą logikę (pozycja bloku po cofnięciu, scalanie ustawień po zapisie) wykonuje BlockEditorStateLogicTest (node --test).
 */
class BlockEditorKeyboardAndSaveTest extends TestCase
{
    private function js(): string
    {
        return file_get_contents(resource_path('js/block-editor.js'));
    }

    private function css(): string
    {
        return file_get_contents(resource_path('css/block-editor.css'));
    }

    /**
     * Ciało funkcji z block-editor.js: od sygnatury do następnej instrukcji na tym samym poziomie wcięcia (4 spacje w start()).
     */
    private function body(string $signature): string
    {
        $js = $this->js();
        $start = strpos($js, $signature);
        $this->assertNotFalse($start, "Brak „{$signature}” w block-editor.js");
        $rest = substr($js, $start + strlen($signature));
        $end = preg_match('/\n    (?=[^\s}])/', $rest, $m, PREG_OFFSET_CAPTURE) ? $m[0][1] : strlen($rest);

        return substr($js, $start, strlen($signature) + $end);
    }

    /** Pozycja napisu w tekście; test pada z czytelnym komunikatem, gdy go nie ma. */
    private function at(string $haystack, string $needle): int
    {
        $pos = strpos($haystack, $needle);
        $this->assertNotFalse($pos, "Brak „{$needle}”");

        return $pos;
    }

    private function cssColor(string $variable): string
    {
        $this->assertSame(1, preg_match('/'.preg_quote($variable, '/').':\s*(#[0-9a-fA-F]{6})/', $this->css(), $m), "Brak zmiennej $variable w block-editor.css");

        return $m[1];
    }

    public function test_changes_made_while_saving_stay_unsaved(): void
    {
        $js = $this->js();
        $save = $this->body('async function save(');

        // Licznik zmian rośnie przy każdej edycji.
        $this->assertStringContainsString('rev: 0,', $js);
        $this->assertMatchesRegularExpression('/function markDirty\(\) \{\s*S\.dirty = true;\s*S\.rev\+\+;/', $js);
        // Licznik i ustawienia są zapamiętywane przed wysłaniem żądania.
        $request = $this->at($save, 'apiFetch(config.urls.save');
        $this->assertLessThan($request, $this->at($save, 'const rev = S.rev;'));
        $this->assertLessThan($request, $this->at($save, 'const sentSettings ='));
        $this->assertLessThan($request, $this->at($save, 'const sentSkipRedirect ='));
        // Flaga „niezapisane” i kopia robocza znikają tylko, gdy w trakcie zapisu nic nie zmieniono — niezależnie od nazwy zmiennej.
        $this->assertSame(1, preg_match('/const\s+(\w+)\s*=\s*S\.rev\s*===\s*rev\s*;/', $save, $m), 'Brak porównania S.rev z wartością sprzed żądania');
        $this->assertLessThan($this->at($save, 'S.dirty = false'), $this->at($save, $m[0]));
        $this->assertSame(1, substr_count($save, 'S.dirty = false'), 'S.dirty = false musi być w save() dokładnie raz (i warunkowo)');
        $this->assertMatchesRegularExpression('/if\s*\(\s*'.$m[1].'\s*\)\s*S\.dirty\s*=\s*false\s*;/', $save);
        $this->assertSame(1, substr_count($save, 'clearDraft()'));
        $this->assertMatchesRegularExpression('/if\s*\(\s*'.$m[1].'\s*\)\s*clearDraft\(\)/', $save);
        $this->assertStringContainsString('Zmiany wprowadzone w trakcie zapisu nie są jeszcze zapisane', $save);
    }

    public function test_settings_changed_while_saving_are_not_overwritten_by_the_response(): void
    {
        $save = $this->body('async function save(');

        // Odpowiedź serwera jest scalana z lokalnymi zmianami, a nie podmienia ustawień w całości.
        $this->assertMatchesRegularExpression('/mergeSavedSettings\(\s*json\.settings\s*,\s*sentSettings\s*,\s*S\.settings\s*\)/', $save);
        $this->assertDoesNotMatchRegularExpression('/S\.settings\s*=\s*json\.settings/', $save);
        // Flagi „ustawienia do zapisania” i „bez przekierowania” zerują się tylko, gdy ustawień nie ruszano w trakcie zapisu.
        $this->assertSame(1, substr_count($save, 'S.settingsDirty = false'));
        $this->assertMatchesRegularExpression('/if\s*\(\s*!\s*merged\.edited\s*\)\s*\{[^}]*S\.settingsDirty\s*=\s*false/s', $save);
        $this->assertSame(1, substr_count($save, 'S.skipSlugRedirect = false'));
        $this->assertMatchesRegularExpression('/if\s*\(\s*!\s*merged\.edited\s*\)\s*\{[^}]*S\.skipSlugRedirect\s*=\s*false/s', $save);
    }

    public function test_network_failures_are_reported_in_polish(): void
    {
        $js = $this->js();

        $this->assertStringContainsString('async function apiFetch(url, init)', $js);
        $this->assertStringContainsString("'Brak połączenia z serwerem. Sprawdź internet i spróbuj ponownie.'", $js);
        // Zapis, import, szablony, historia, przesyłanie plików i podglądy modułów idą przez apiFetch (wyjątek: ciche podpowiedzi).
        foreach (['config\.urls\.save', 'config\.urls\.docx', 'config\.urls\.templates\.base', 'config\.urls\.history', 'config\.urls\.upload'] as $url) {
            $this->assertMatchesRegularExpression('/apiFetch\(\s*'.$url.'/', $js);
            $this->assertDoesNotMatchRegularExpression('/(?<![A-Za-z])fetch\(\s*'.$url.'/', $js);
        }
        $this->assertMatchesRegularExpression('/const res = await apiFetch\(\s*url,/', $this->body('async function postJson('));
    }

    public function test_undo_redo_keeps_focus_and_returns_to_the_nearest_block_not_the_page_start(): void
    {
        $restore = $this->body('function restore(json)');

        // Fokus i ścieżka zaznaczonego bloku są zapamiętywane PRZED podmianą bloków i przerysowaniem płótna.
        $swap = $this->at($restore, 'S.blocks = data.blocks');
        $render = $this->at($restore, 'renderAll()');
        $this->assertLessThan($render, $this->at($restore, 'canvas.contains(document.activeElement)'));
        $this->assertLessThan($swap, $this->at($restore, 'blockPath(S.blocks, S.selectedId)'));
        $this->assertLessThan($swap, $this->at($restore, 'const previous = S.blocks;'));
        // Gdy zaznaczony blok zniknął, wracamy na jego pozycję; nigdy na początek strony.
        $this->assertStringContainsString('nearestBlockId(previous, S.blocks, path)', $restore);
        $this->assertStringNotContainsString('S.blocks[0]', $restore);
        $this->assertDoesNotMatchRegularExpression('/S\.selectedId\s*=\s*null/', $restore);
        // Fokus ustawiany po przerysowaniu: na bloku albo (puste drzewo) na polu dodawania, bez przewijania.
        $this->assertGreaterThan($render, $this->at($restore, 'focusBlock(S.selectedId'));
        $this->assertGreaterThan($render, $this->at($restore, "canvas.querySelector('.be-root-appender-text')?.focus({ preventScroll: true })"));
    }

    public function test_closing_the_left_panel_returns_focus_to_what_opened_it(): void
    {
        $js = $this->js();
        $toggle = $this->body('function togglePanel(');
        $close = $this->body('function closePanel(');
        $insert = $this->body('function insertFromPanel(');

        // Kontrolka otwierająca jest zapamiętywana, zanim panel przejmie fokus.
        $this->assertLessThan($this->at($toggle, 'renderPanel()'), $this->at($toggle, 'const opener = document.activeElement;'));
        $this->assertStringContainsString('fromTopbar: !target', $toggle);
        $this->assertStringContainsString("opener.closest?.('.be-topbar')", $toggle);
        // Fokus panelu i otwierający są odczytywane przed przerysowaniem (po nim activeElement to body).
        $render = $this->at($close, 'renderPanel()');
        $this->assertLessThan($render, $this->at($close, 'leftPanel.contains(document.activeElement)'));
        $this->assertLessThan($render, $this->at($close, 'const opener = panelOpener'));
        // Kolejność celów: kontrolka otwierająca → przycisk paska o tym samym kluczu → zaznaczony blok (panel z treści) → przycisk panelu.
        $order = [
            $this->at($close, 'opener?.el?.isConnected'),
            $this->at($close, 'topbarButton(opener?.key)'),
            $this->at($close, 'wrapperOf(S.selectedId)'),
            strrpos($close, 'topbarButton(PANEL_BUTTONS[name])'),
        ];
        $sorted = $order;
        sort($sorted);
        $this->assertSame($sorted, $order, 'Cele fokusu w closePanel() w złej kolejności');
        // Wstawienie bloku z panelu ustawia fokus samo — nie przeskakuje przez pasek.
        $this->assertStringContainsString('closePanel({ restoreFocus: false })', $insert);
        // Esc w lewym panelu zamyka go (także widok listy i dostępność); przycisk „Zamknij panel” nie przekazuje zdarzenia.
        $this->assertMatchesRegularExpression("/leftPanel\.addEventListener\('keydown',[^\n]*Escape[^\n]*closePanel\(\)/", $js);
        $this->assertStringContainsString("onClick: () => closePanel() }, icon('xmark')", $js);
        // Panel Ustawienia: „Zamknij ustawienia” wraca na przycisk „Ustawienia” w górnym pasku.
        $this->assertStringContainsString("topbar.querySelector('[data-key=\"Ustawienia\"]')?.focus()", $js);
        // Klucze przycisków istnieją w pasku.
        foreach (["tbButton('Dodaj blok'", "tbButton('Widok listy'", "'data-key': 'a11y'", "tbButton('Ustawienia'"] as $needle) {
            $this->assertStringContainsString($needle, $js);
        }
        // Okno skrótów opisuje Esc w lewym panelu.
        $this->assertStringContainsString("['Esc', 'W lewym panelu (Dodaj blok, Widok listy, Dostępność): zamknij panel i wróć tam, skąd go otwarto']", $js);
    }

    public function test_rerendering_keeps_focus_in_the_block_toolbar_sidebar_and_menus(): void
    {
        $js = $this->js();
        $rerender = $this->body('function rerenderBlock(');
        $sidebar = $this->body('function renderSidebar(');

        // Pasek bloku leży w przerysowywanym bloku: przycisk z fokusem jest zapamiętany przed podmianą, a przywracany po przebudowie paska.
        $this->assertLessThan($this->at($rerender, 'old.replaceWith('), $this->at($rerender, 'toolbar.contains(document.activeElement)'));
        $this->assertGreaterThan($this->at($rerender, 'applySelection()'), $this->at($rerender, 'toolButtons()[toolAt]?.focus()'));
        // Panel boczny: fokus i kursor są odczytywane przed przerysowaniem, odtwarzane po nim.
        $replace = $this->at($sidebar, 'sidebar.replaceChildren(');
        $this->assertLessThan($replace, $this->at($sidebar, 'sidebar.contains(document.activeElement)'));
        $this->assertLessThan($replace, $this->at($sidebar, 'active.selectionStart'));
        $this->assertGreaterThan($replace, $this->at($sidebar, 'setSelectionRange(caret.start, caret.end'));
        $this->assertGreaterThan($replace, $this->at($sidebar, 'data-focus-key="${focusKey}"'));
        // Kontrolka wyłączona nie przyjmie fokusu — szukamy najbliższej, która przyjmie.
        $this->assertStringContainsString('document.activeElement !== all[i]', $sidebar);
        // Pozycja menu „Więcej opcji” (np. „Kopiuj blok”) oddaje fokus przyciskowi menu zamiast go zgubić.
        $this->assertStringContainsString('onClick: () => { close(true); it.onClick(); },', $js);
        // „Dodaj przycisk” przenosi fokus na napis nowego przycisku.
        $this->assertStringContainsString("wrapperOf(b.id)?.querySelectorAll('.be-button-label')", $js);
    }

    public function test_removing_or_moving_items_in_the_sidebar_moves_focus_to_a_safe_target(): void
    {
        $js = $this->js();

        // Nagłówek sekcji przyjmuje fokus programowo i ma klucz — to bezpieczny cel po usunięciu elementu.
        $this->assertStringContainsString('h3\', { tabindex: \'-1\', \'data-focus-key\': \'section:\' + title }', $js);
        // Galeria: usunięcie → nagłówek „Zdjęcia”; przesunięcie → przycisk przesuniętego zdjęcia; wszystko z komunikatem dla czytnika.
        $this->assertStringContainsString('renderSidebar({ focusKey: \'section:Zdjęcia\' }); announce(`Usunięto zdjęcie ${i + 1}`);', $js);
        $this->assertStringContainsString('\'data-focus-key\': `gal-up-${i}`', $js);
        $this->assertStringContainsString('\'data-focus-key\': `gal-down-${i}`', $js);
        $this->assertStringContainsString('renderSidebar({ focusKey: i - 1 > 0 ?', $js);
        $this->assertStringContainsString('renderSidebar({ focusKey: i + 1 < imgs.length - 1 ?', $js);
        // Przyciski: usunięcie → nagłówek przycisku, który zajął to miejsce.
        $this->assertStringContainsString('renderSidebar({ focusKey: `section:Przycisk ${Math.min(i, a.items.length - 1) + 1}` });', $js);
        $this->assertStringContainsString('announce(`Usunięto przycisk ${i + 1}`);', $js);
    }

    public function test_accessibility_button_keeps_its_name_when_text_is_hidden_on_narrow_screens(): void
    {
        $js = $this->js();
        $css = $this->css();

        $this->assertStringContainsString("'aria-label': a11yLabel, title: a11yLabel", $js);
        $this->assertStringContainsString("h('span', { class: 'be-a11y-count', 'aria-hidden': 'true' }", $js);
        // Tekst obok ikony jest ukrywany na wąskich ekranach (display: none) — dlatego nazwa musi być w aria-label.
        $this->assertStringContainsString('.be-a11y-btn span, .be-topbar-center', $css);
        $this->assertStringContainsString('.be-a11y-btn .be-a11y-count { display: inline;', $css);
        $this->assertStringContainsString('.be-a11y-count { display: none; }', $css);
    }

    public function test_saving_state_of_primary_button_stays_readable(): void
    {
        $css = $this->css();

        // Zapis w toku: biały na ciemnoniebieskim; wyłączony: ciemnoszary na jasnym tle — oba ≥ 4,5:1.
        $this->assertGreaterThanOrEqual(4.5, Color::contrast('#ffffff', $this->cssColor('--be-ui-dark')));
        $this->assertGreaterThanOrEqual(4.5, Color::contrast($this->cssColor('--be-muted'), $this->cssColor('--be-bg')));
        $this->assertStringContainsString('.be-btn--primary[aria-disabled="true"], .be-btn--primary[aria-disabled="true"]:hover { background: var(--be-ui-dark); color: #fff;', $css);
        $this->assertStringContainsString('.be-btn--primary:disabled, .be-btn--primary:disabled:hover { background: var(--be-bg); color: var(--be-muted); }', $css);

        // Reguły muszą stać po ogólnym stanie wyłączonym (równa specyficzność — wygrywa późniejsza).
        $generic = strpos($css, '.be-btn[aria-disabled="true"], .be-btn:disabled {');
        $genericHover = strpos($css, '.be-btn[aria-disabled="true"]:hover, .be-btn:disabled:hover {');
        $primary = strpos($css, '.be-btn--primary[aria-disabled="true"], .be-btn--primary[aria-disabled="true"]:hover');
        $this->assertNotFalse($generic);
        $this->assertNotFalse($genericHover);
        $this->assertGreaterThan($genericHover, $primary);
        $this->assertGreaterThan($generic, $primary);
    }

    public function test_smooth_scrolling_respects_reduced_motion(): void
    {
        $js = $this->js();

        // Każde użycie „smooth” w JS jest warunkowane preferencją użytkownika; w CSS nie ma wymuszonego płynnego przewijania.
        $this->assertGreaterThan(0, preg_match_all('/.{0,160}smooth.{0,20}/', $js, $matches));
        foreach ($matches[0] as $line) {
            $this->assertStringContainsString('prefers-reduced-motion', $line, "Płynne przewijanie bez sprawdzenia preferencji: {$line}");
        }
        $this->assertDoesNotMatchRegularExpression('/scroll-behavior\s*:\s*smooth/', $this->css());
    }
}
