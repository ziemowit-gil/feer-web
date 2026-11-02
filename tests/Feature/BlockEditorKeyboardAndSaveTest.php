<?php

namespace Tests\Feature;

use App\Support\Color;
use Tests\TestCase;

/**
 * Edytor blokowy: zapis (zmiany z trakcji zapisu, komunikat o braku sieci), fokus klawiatury po przerysowaniach
 * (cofnij/ponów, zamykanie paneli, pasek bloku, panel boczny), nazwa przycisku dostępności na wąskich ekranach,
 * kontrast przycisku zapisu i ruch przy prefers-reduced-motion. JS i CSS sprawdzamy statycznie, jak pozostałe testy edytora.
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

    private function cssColor(string $variable): string
    {
        $this->assertSame(1, preg_match('/'.preg_quote($variable, '/').':\s*(#[0-9a-fA-F]{6})/', $this->css(), $m), "Brak zmiennej $variable w block-editor.css");

        return $m[1];
    }

    public function test_changes_made_while_saving_stay_unsaved(): void
    {
        $js = $this->js();

        // Licznik zmian rośnie przy każdej edycji, a zapis porównuje go sprzed i po żądaniu.
        $this->assertStringContainsString('rev: 0,', $js);
        $this->assertMatchesRegularExpression('/function markDirty\(\) \{\s*S\.dirty = true;\s*S\.rev\+\+;/', $js);
        $this->assertStringContainsString('const rev = S.rev;', $js);
        $this->assertStringContainsString('const untouched = S.rev === rev;', $js);
        // Bez zmian w trakcie: jak dotąd. Ze zmianami: flaga „niezapisane” i kopia robocza zostają.
        $this->assertStringContainsString('if (untouched) S.dirty = false;', $js);
        $this->assertStringContainsString('if (untouched) clearDraft();', $js);
        $this->assertStringContainsString("'Zapisano. Zmiany wprowadzone w trakcie zapisu nie są jeszcze zapisane — zapisz ponownie.'", $js);
        // Stary, bezwarunkowy wariant nie może wrócić.
        $this->assertStringNotContainsString("await saveNewsSettings();\n            S.dirty = false;", $js);
    }

    public function test_network_failures_are_reported_in_polish(): void
    {
        $js = $this->js();

        $this->assertStringContainsString('async function apiFetch(url, init)', $js);
        $this->assertStringContainsString("'Brak połączenia z serwerem. Sprawdź internet i spróbuj ponownie.'", $js);
        // Zapis, import, szablony, historia, przesyłanie plików i podglądy modułów idą przez apiFetch (wyjątek: ciche podpowiedzi).
        foreach (['config.urls.save', 'config.urls.docx', 'config.urls.templates.base', 'config.urls.history', 'config.urls.upload'] as $url) {
            $this->assertStringContainsString("apiFetch($url", $js);
            $this->assertStringNotContainsString("await fetch($url", $js);
        }
        $this->assertStringContainsString('const res = await apiFetch(url, {', $js);
    }

    public function test_focus_is_kept_after_undo_redo_and_when_left_panels_close(): void
    {
        $js = $this->js();

        // Cofnij/ponów przerysowuje płótno — fokus wraca do zaznaczonego bloku, jeśli był na płótnie.
        $this->assertStringContainsString('const hadFocus = canvas.contains(document.activeElement);', $js);
        $this->assertStringContainsString("if (inNavigation) selectBlock(id, { focusWrapper: true }); else focusBlock(id, 'end');", $js);
        // Zamknięcie lewego panelu (Esc, „Zamknij panel”) oddaje fokus przyciskowi, który go otworzył.
        $this->assertStringContainsString("const PANEL_BUTTONS = { inserter: 'Dodaj blok', list: 'Widok listy', a11y: 'a11y' };", $js);
        $this->assertStringContainsString('topbar.querySelector(`[data-key="${PANEL_BUTTONS[name]}"]`)?.focus()', $js);
        $this->assertStringContainsString("leftPanel.addEventListener('keydown', (e) => { if (e.key === 'Escape' && S.panel)", $js);
        // Panel Ustawienia też: „Zamknij ustawienia” wraca na przycisk „Ustawienia” w górnym pasku.
        $this->assertStringContainsString("topbar.querySelector('[data-key=\"Ustawienia\"]')?.focus()", $js);
        // Klucze przycisków istnieją w pasku.
        $this->assertStringContainsString("tbButton('Dodaj blok'", $js);
        $this->assertStringContainsString("tbButton('Widok listy'", $js);
        $this->assertStringContainsString("'data-key': 'a11y'", $js);
        $this->assertStringContainsString("tbButton('Ustawienia'", $js);
    }

    public function test_rerendering_keeps_focus_in_the_block_toolbar_sidebar_and_menus(): void
    {
        $js = $this->js();

        // Przycisk paska bloku (np. „Wyśrodkuj”) znika z przerysowanym blokiem — fokus wraca na ten sam przycisk.
        $this->assertStringContainsString('const toolAt = toolbar.contains(document.activeElement)', $js);
        $this->assertStringContainsString('if (toolAt >= 0) toolButtons()[toolAt]?.focus();', $js);
        // Panel boczny: kontrolka zmieniana z klawiatury (select, pole wyboru) nie gubi fokusu po przerysowaniu panelu.
        $this->assertStringContainsString('const focusedAt = sidebar.contains(document.activeElement)', $js);
        $this->assertStringContainsString('all[Math.min(focusedAt, all.length - 1)]?.focus();', $js);
        // Pozycja menu „Więcej opcji” (np. „Kopiuj blok”) oddaje fokus przyciskowi menu zamiast go zgubić.
        $this->assertStringContainsString('onClick: () => { close(true); it.onClick(); },', $js);
        // „Dodaj przycisk” przenosi fokus na napis nowego przycisku.
        $this->assertStringContainsString("wrapperOf(b.id)?.querySelectorAll('.be-button-label')", $js);
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

        $this->assertStringContainsString("window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth'", $js);
        $this->assertStringNotContainsString("behavior: 'smooth'", $js);
    }
}
