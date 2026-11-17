<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Support\BlockRenderer;
use App\Support\BlockSummary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * „Treść strony” w karcie Strony Nawigator (jak Page module w TYPO3): operacje na blokach najwyższego poziomu
 * bez otwierania pełnego edytora — przesunięcie wyżej/niżej, ukrycie/pokazanie i szybka edycja tekstu
 * (nagłówek, akapit, lista bez formatowania). Każdy zapis idzie przez updateFromBlockEditor (bloki zostają,
 * `content` renderowany z bloków, nowa wersja w historii).
 *
 * Bloki wskazuje numer (`index`), a formularz niesie skrót wersji bloków (`v`): jeśli ktoś w międzyczasie
 * zmienił treść, operacja jest odrzucana zamiast zadziałać na innym bloku.
 */
class PageBlocksController extends Controller
{
    /** Skrót wersji bloków strony (do wykrywania równoczesnej zmiany). */
    public static function version(Page $page): string
    {
        return substr(md5(json_encode($page->blocks ?? [])), 0, 12);
    }

    public function move(Request $request, Page $page, int $index): RedirectResponse
    {
        [$blocks, $error] = $this->load($request, $page, $index);
        if ($error) {
            return $error;
        }
        $to = $request->input('kierunek') === 'gora' ? $index - 1 : $index + 1;
        if ($to < 0 || $to >= count($blocks)) {
            return $this->back($page)->with('error', 'Tego bloku nie da się przesunąć dalej.');
        }
        [$blocks[$index], $blocks[$to]] = [$blocks[$to], $blocks[$index]];
        $this->save($page, $blocks);
        $label = BlockSummary::describe($blocks[$to])['label'];

        return $this->back($page)->with('status', "Przesunięto blok „{$label}” na pozycję ".($to + 1).' z '.count($blocks).'.');
    }

    public function toggle(Request $request, Page $page, int $index): RedirectResponse
    {
        [$blocks, $error] = $this->load($request, $page, $index);
        if ($error) {
            return $error;
        }
        $hide = empty($blocks[$index]['hidden']);
        if ($hide) {
            $blocks[$index]['hidden'] = true;
        } else {
            unset($blocks[$index]['hidden']);
        }
        $this->save($page, $blocks);
        $label = BlockSummary::describe($blocks[$index])['label'];

        return $this->back($page)->with('status', $hide
            ? "Ukryto blok „{$label}” — nie widać go na stronie, ale zostaje w treści."
            : "Blok „{$label}” znów jest widoczny na stronie.");
    }

    public function update(Request $request, Page $page, int $index): RedirectResponse
    {
        [$blocks, $error] = $this->load($request, $page, $index);
        if ($error) {
            return $error;
        }
        $block = $blocks[$index];
        if (! BlockSummary::quickEditable($block)) {
            return $this->back($page)->with('error', 'Ten blok poprawisz w edytorze blokowym.');
        }

        $data = $request->validate([
            'tekst' => ['required', 'string', 'max:20000'],
            'poziom' => ['nullable', 'integer', 'in:2,3,4'],
        ], [], ['tekst' => 'tekst bloku']);

        $lines = preg_split('/\R/u', trim($data['tekst']));
        $html = fn (string $s) => e(trim($s));
        match ($block['type']) {
            'heading' => $block['attrs'] = ['text' => $html(implode(' ', $lines)), 'level' => (int) ($data['poziom'] ?? $block['attrs']['level'] ?? 2)] + $block['attrs'],
            'paragraph' => $block['attrs'] = ['text' => implode('<br>', array_map($html, $lines))] + $block['attrs'],
            'list' => $block['attrs'] = ['items' => array_values(array_filter(array_map($html, $lines), fn ($i) => $i !== ''))] + $block['attrs'],
        };
        $blocks[$index] = $block;
        $this->save($page, $blocks);

        return $this->back($page)->with('status', 'Zapisano blok „'.BlockSummary::describe($block)['label'].'”.');
    }

    /** @return array{0: array<int, array<string, mixed>>, 1: ?RedirectResponse} */
    private function load(Request $request, Page $page, int $index): array
    {
        abort_unless(SiteSetting::current()->isModuleEnabled('block_editor'), 404);
        abort_unless($request->user()->canAccessModule('pages'), 403);
        abort_unless(Page::forCurrentSite()->whereKey($page->id)->exists(), 404);
        if ($page->is_locked && ! $request->user()->isAdmin()) {
            return [[], $this->back($page)->with('error', "Strona „{$page->title}” jest zablokowana do edycji przez administratora.")];
        }
        $blocks = is_array($page->blocks) ? array_values($page->blocks) : [];
        if ($request->input('v') !== self::version($page) || ! isset($blocks[$index])) {
            return [[], $this->back($page)->with('error', 'Treść strony zmieniła się w międzyczasie (np. w edytorze blokowym). Sprawdź ją i spróbuj ponownie.')];
        }

        return [$blocks, null];
    }

    private function save(Page $page, array $blocks): void
    {
        $blocks = BlockRenderer::normalize($blocks);
        $page->updateFromBlockEditor(['blocks' => $blocks, 'content' => BlockRenderer::render($blocks)]);
    }

    private function back(Page $page): RedirectResponse
    {
        return redirect()->to(route('admin.podstrony.index', ['wybrana' => $page->id]).'#tresc-strony');
    }
}
