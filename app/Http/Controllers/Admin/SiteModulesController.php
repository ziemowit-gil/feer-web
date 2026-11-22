<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Ustawienia → Moduły: akcje masowe w tabelach „Moduły witryny” i „Typy treści i układy”.
 *
 * Zwykłe formularze (bez JS): zaznaczone wiersze (`modules[]` albo `options[]` = „opt:grupa:wartość”) + przycisk akcji (`action`),
 * albo przycisk w wierszu (`row_action` = „akcja|klucz” / „akcja|grupa|wartość”), który ma pierwszeństwo przed
 * zaznaczeniem. Zmiana zapisuje się od razu w bieżącej witrynie; po akcji wracamy do tabeli z komunikatem.
 */
class SiteModulesController extends Controller
{
    public const MODULE_ACTIONS = ['enable' => 'Włączono', 'disable' => 'Wyłączono'];

    public const OPTION_ACTIONS = ['show' => 'Udostępniono', 'hide' => 'Ukryto'];

    /**
     * Włącz / Wyłącz zaznaczone moduły (albo jeden — przycisk w wierszu). Przycisk spoza tabeli (np. „Wyłącz
     * działania” w Ustawienia → Szablon) wysyła `return_to=template` i `anchor` — wracamy wtedy do zakładki Szablon,
     * a komunikat trafia do zwykłego paska stanu panelu.
     */
    public function modules(Request $request): RedirectResponse
    {
        $fromTemplate = $request->input('return_to') === 'template';
        $anchor = preg_match('/^[a-z0-9-]{1,40}$/', (string) $request->input('anchor')) ? (string) $request->input('anchor') : 'szablon';
        $back = $fromTemplate
            ? route('admin.ustawienia.edit', ['tab' => 'template']).'#'.$anchor
            : route('admin.ustawienia.edit', ['tab' => 'modules']).'#tabela-modulow';
        $fail = fn (string $message) => $fromTemplate
            ? redirect()->to($back)->with('error', $message)
            : redirect()->to($back)->withErrors(['modules' => $message], 'modulesTable');

        [$action, $keys] = $this->moduleSelection($request);

        if (! isset(self::MODULE_ACTIONS[$action])) {
            return $fail('Wybierz akcję: Włącz albo Wyłącz zaznaczone.');
        }

        $keys = array_values(array_intersect(array_keys(SiteSetting::MODULES), $keys));
        if ($keys === []) {
            return $fail('Zaznacz co najmniej jeden moduł, a potem wybierz akcję.');
        }

        $site = SiteSetting::current();
        $disabled = $site->disabled_modules ?? [];
        $changed = $action === 'enable'
            ? array_values(array_intersect($keys, $disabled))
            : array_values(array_diff($keys, $disabled));

        $disabled = $action === 'enable'
            ? array_values(array_diff($disabled, $keys))
            : array_values(array_unique(array_merge($disabled, $keys)));

        if ($changed !== []) {
            $site->update(['disabled_modules' => $disabled]);
        }

        $label = fn (string $key) => SiteSetting::MODULES[$key] ?? $key;
        $message = $changed === []
            ? 'Bez zmian — zaznaczone moduły już były '.($action === 'enable' ? 'włączone' : 'wyłączone').'.'
            : self::MODULE_ACTIONS[$action].' '.count($changed).' '.self::plural(count($changed), 'moduł', 'moduły', 'modułów').': '
                .implode(', ', array_map($label, $changed)).'.';

        $unchanged = array_values(array_diff($keys, $changed));
        if ($changed !== [] && $unchanged !== []) {
            $message .= ' Bez zmian (już '.($action === 'enable' ? 'włączone' : 'wyłączone').'): '.implode(', ', array_map($label, $unchanged)).'.';
        }

        return redirect()->to($back)->with($fromTemplate ? 'status' : 'modules_table_status', $message);
    }

    /** Udostępnij / Ukryj zaznaczone typy treści i układy (albo jeden — przycisk w wierszu). */
    public function options(Request $request): RedirectResponse
    {
        $back = route('admin.ustawienia.edit', ['tab' => 'modules']).'#tabela-typow-tresci';
        $groups = SiteSetting::blockableOptionGroups();

        [$action, $selected] = $this->optionSelection($request);

        if (! isset(self::OPTION_ACTIONS[$action])) {
            return redirect()->to($back)->withErrors(['options' => 'Wybierz akcję: Udostępnij albo Ukryj zaznaczone.'], 'optionsTable');
        }

        // Tylko istniejące grupy i wartości.
        $selected = collect($selected)
            ->only(array_keys($groups))
            ->map(fn ($values, $group) => array_values(array_intersect(array_map('strval', array_keys($groups[$group])), array_map('strval', (array) $values))))
            ->filter()
            ->all();

        if ($selected === []) {
            return redirect()->to($back)->withErrors(['options' => 'Zaznacz co najmniej jeden typ treści lub układ, a potem wybierz akcję.'], 'optionsTable');
        }

        $site = SiteSetting::current();
        $blocked = $site->blocked_options ?? [];
        $changed = [];
        $skipped = [];
        $errors = [];

        foreach ($selected as $group => $values) {
            $current = array_map('strval', (array) ($blocked[$group] ?? []));
            $protected = array_map('strval', SiteSetting::UNBLOCKABLE_OPTIONS[$group] ?? []);

            if ($action === 'hide') {
                $skipped = array_merge($skipped, array_map(fn ($v) => [$group, $v], array_intersect($values, $protected)));
                $values = array_values(array_diff($values, $protected));
                $next = array_values(array_unique(array_merge($current, $values)));
                $changedHere = array_values(array_diff($values, $current));
            } else {
                $next = array_values(array_diff($current, $values));
                $changedHere = array_values(array_intersect($values, $current));
            }

            $all = array_map('strval', array_keys($groups[$group]));
            if (array_diff($all, $next) === []) {
                $errors[] = SiteSetting::BLOCKABLE_GROUP_LABELS[$group] ?? $group;

                continue;
            }

            $blocked[$group] = $next;
            foreach ($changedHere as $value) {
                $changed[] = [$group, $value];
            }
        }

        if ($errors !== []) {
            return redirect()->to($back)->withErrors(['options' => 'Nie można ukryć wszystkich opcji w grupie: '.implode(', ', $errors)
                .'. Zostaw dostępną co najmniej jedną — nic nie zostało zmienione.'], 'optionsTable');
        }

        if ($changed !== []) {
            $site->update(['blocked_options' => $blocked]);
        }

        $label = fn (array $item) => ($groups[$item[0]][$item[1]] ?? $item[1]).' ('.(SiteSetting::BLOCKABLE_GROUP_LABELS[$item[0]] ?? $item[0]).')';
        $message = $changed === []
            ? 'Bez zmian — zaznaczone opcje już były '.($action === 'show' ? 'dostępne' : 'ukryte').'.'
            : self::OPTION_ACTIONS[$action].' '.count($changed).' '.self::plural(count($changed), 'opcję', 'opcje', 'opcji').': '
                .implode(', ', array_map($label, $changed)).'.';

        if ($skipped !== []) {
            $message .= ' Zawsze dostępne, pominięto: '.implode(', ', array_map($label, $skipped)).'.';
        }

        return redirect()->to($back)->with('options_table_status', $message);
    }

    /** @return array{0: string, 1: list<string>} */
    private function moduleSelection(Request $request): array
    {
        $row = (string) $request->input('row_action', '');
        if ($row !== '' && str_contains($row, '|')) {
            [$action, $key] = explode('|', $row, 2);

            return [$action, [$key]];
        }

        return [(string) $request->input('action', ''), array_map('strval', array_filter((array) $request->input('modules', []), 'is_scalar'))];
    }

    /** @return array{0: string, 1: array<string, list<string>>} */
    private function optionSelection(Request $request): array
    {
        $row = (string) $request->input('row_action', '');
        if ($row !== '' && substr_count($row, '|') >= 2) {
            [$action, $group, $value] = explode('|', $row, 3);

            return [$action, [$group => [$value]]];
        }

        // Zaznaczone wiersze: options[] = „opt:grupa:wartość” (jak klucze filtra w widoku).
        $options = [];
        foreach ((array) $request->input('options', []) as $item) {
            if (is_scalar($item) && substr_count((string) $item, ':') >= 2) {
                [, $group, $value] = explode(':', (string) $item, 3);
                $options[$group][] = $value;
            }
        }

        return [(string) $request->input('action', ''), $options];
    }

    private static function plural(int $n, string $one, string $few, string $many): string
    {
        $t = $n % 10;
        $h = $n % 100;

        return $n === 1 ? $one : ($t >= 2 && $t <= 4 && ($h < 12 || $h > 14) ? $few : $many);
    }
}
