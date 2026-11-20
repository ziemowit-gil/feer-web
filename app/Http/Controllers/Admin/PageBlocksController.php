<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentBlock;
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

        $block = $this->applyText($block, $data);
        $blocks[$index] = $block;
        $this->save($page, $blocks);

        return $this->back($page)->with('status', 'Zapisano blok „'.BlockSummary::describe($block)['label'].'”.');
    }

    /**
     * „Udostępnij jako blok wspólny”: blok trafia do bloków wspólnych witryny, a na stronie zastępuje go wstawka
     * [blok:ID] — wygląd strony się nie zmienia, a ten sam blok można wstawić na innych stronach.
     */
    public function share(Request $request, Page $page, int $index): RedirectResponse
    {
        [$blocks, $error] = $this->load($request, $page, $index);
        if ($error) {
            return $error;
        }
        if (BlockSummary::sharedId($blocks[$index]) !== null) {
            return $this->back($page)->with('error', 'Ten blok już jest blokiem wspólnym.');
        }
        $sum = BlockSummary::describe($blocks[$index]);
        $name = trim((string) $request->input('nazwa')) ?: mb_strimwidth($sum['label'].($sum['text'] !== '' ? ': '.$sum['text'] : '').' ('.$page->title.')', 0, 180, '…');

        // Ukrycie i harmonogram zostają przy wstawce na tej stronie, nie w bloku wspólnym.
        $flags = array_intersect_key($blocks[$index], array_flip(['hidden', 'from', 'until']));
        $shared = ContentBlock::create(['type' => ContentBlock::SHARED, 'name' => $name,
            'data' => ['blocks' => BlockRenderer::normalize([array_diff_key($blocks[$index], $flags)])], 'created_by' => $request->user()->id]);
        $blocks[$index] = ['type' => 'shortcode', 'attrs' => ['code' => '[blok:'.$shared->id.']']] + $flags;
        $this->save($page, $blocks);

        return $this->back($page)->with('status', "Blok jest teraz blokiem wspólnym „{$name}”. Wstawisz go na innych stronach przyciskiem „Wstaw blok wspólny” w ich karcie.");
    }

    /** „Wstaw blok wspólny”: dodaje wstawkę [blok:ID] na końcu treści strony (potem można ją przesunąć). */
    public function insertShared(Request $request, Page $page): RedirectResponse
    {
        [$blocks, $error] = $this->load($request, $page, null);
        if ($error) {
            return $error;
        }
        if (! \App\Support\EditorPermissions::allowsType($request->user()->allowedBlockTypes(), 'shortcode')) {
            return $this->back($page)->with('error', 'Twoja grupa nie może wstawiać bloków wspólnych ani innych wstawek.');
        }
        $shared = ContentBlock::forCurrentSite()->where('type', ContentBlock::SHARED)->find((int) $request->input('blok'));
        if (! $shared) {
            return $this->back($page)->with('error', 'Wybierz blok wspólny z listy.');
        }
        $blocks[] = ['type' => 'shortcode', 'attrs' => ['code' => '[blok:'.$shared->id.']']];
        $this->save($page, $blocks);

        return $this->back($page)->with('status', "Wstawiono blok wspólny „{$shared->name}” na końcu treści (pozycja ".count($blocks).').');
    }

    /** „Odłącz”: wstawkę bloku wspólnego zastępuje kopia jego bloków — dalsze zmiany bloku wspólnego tej strony nie dotyczą. */
    public function detach(Request $request, Page $page, int $index): RedirectResponse
    {
        [$blocks, $error] = $this->load($request, $page, $index);
        if ($error) {
            return $error;
        }
        $shared = ($id = BlockSummary::sharedId($blocks[$index])) ? ContentBlock::forCurrentSite()->find($id) : null;
        if (! $shared || ! $shared->isShared()) {
            return $this->back($page)->with('error', 'To nie jest blok wspólny.');
        }
        array_splice($blocks, $index, 1, (array) ($shared->data['blocks'] ?? []));
        $this->save($page, $blocks);

        return $this->back($page)->with('status', "Odłączono blok wspólny „{$shared->name}” — na tej stronie jest teraz jego kopia.");
    }

    /** Szybka poprawka bloku wspólnego (nagłówek, akapit, lista bez formatowania) — zmienia go na wszystkich stronach. */
    public function updateShared(Request $request, Page $page, ContentBlock $block): RedirectResponse
    {
        abort_unless(SiteSetting::current()->isModuleEnabled('block_editor') && $request->user()->canAccessModule('pages'), 403);
        abort_unless($block->site_id === SiteSetting::current()->id && $block->isShared(), 404);
        $inner = array_values((array) ($block->data['blocks'] ?? []));
        if (count($inner) !== 1 || ! BlockSummary::quickEditable($inner[0])) {
            return $this->back($page)->with('error', 'Ten blok wspólny poprawisz w edytorze blokowym — przycisk „Edytuj blok wspólny” przy nim.');
        }
        $data = $request->validate([
            'tekst' => ['required', 'string', 'max:20000'],
            'poziom' => ['nullable', 'integer', 'in:2,3,4'],
        ], [], ['tekst' => 'tekst bloku']);
        $block->update(['data' => ['blocks' => BlockRenderer::normalize([$this->applyText($inner[0], $data)])] + (array) $block->data]);
        $n = $block->usedOnPages()->count();

        return $this->back($page)->with('status', "Zapisano blok wspólny „{$block->name}” — zmiana widać na stronach: {$n}.");
    }

    /** Wstawia tekst z formularza szybkiej edycji do bloku (nagłówek, akapit, lista). */
    private function applyText(array $block, array $data): array
    {
        $lines = preg_split('/\R/u', trim($data['tekst']));
        $html = fn (string $s) => e(trim($s));
        $block['attrs'] = match ($block['type']) {
            'heading' => ['text' => $html(implode(' ', $lines)), 'level' => (int) ($data['poziom'] ?? $block['attrs']['level'] ?? 2)] + $block['attrs'],
            'paragraph' => ['text' => implode('<br>', array_map($html, $lines))] + $block['attrs'],
            'list' => ['items' => array_values(array_filter(array_map($html, $lines), fn ($i) => $i !== ''))] + $block['attrs'],
        };

        return $block;
    }

    /** @return array{0: array<int, array<string, mixed>>, 1: ?RedirectResponse} */
    private function load(Request $request, Page $page, ?int $index): array
    {
        abort_unless(SiteSetting::current()->isModuleEnabled('block_editor'), 404);
        abort_unless($request->user()->canAccessModule('pages'), 403);
        abort_unless(Page::forCurrentSite()->whereKey($page->id)->exists(), 404);
        if ($page->is_locked && ! $request->user()->isAdmin()) {
            return [[], $this->back($page)->with('error', "Strona „{$page->title}” jest zablokowana do edycji przez administratora.")];
        }
        $blocks = is_array($page->blocks) ? array_values($page->blocks) : [];
        if ($request->input('v') !== self::version($page) || ($index !== null && ! isset($blocks[$index]))) {
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
