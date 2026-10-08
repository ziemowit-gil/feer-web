<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuickAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * Panel admin: zarządzanie szybkimi akcjami (kafelki z ikonami widoczne na stronie głównej).
 *
 * Metody: index(), create(), store(), edit(), update(), destroy().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class QuickActionController extends Controller
{
    /** Wyświetla listę szybkich akcji posortowanych wg kolejności. */
    public function index()
    {
        $quickActions = QuickAction::forCurrentSite()->orderBy('order')->get();

        return view('admin.quick-actions.index', compact('quickActions'));
    }

    /** Wyświetla formularz tworzenia nowej szybkiej akcji. */
    public function create()
    {
        return view('admin.quick-actions.form', ['quickAction' => new QuickAction]);
    }

    /** Zapisuje nową szybką akcję. */
    public function store(Request $request)
    {
        QuickAction::create($this->validated($request));

        return redirect()->route('admin.szybkie-akcje.index')->with('status', 'Szybka akcja została dodana.');
    }

    /** Wyświetla formularz edycji szybkiej akcji. */
    public function edit(QuickAction $quickAction)
    {
        return view('admin.quick-actions.form', compact('quickAction'));
    }

    /** Aktualizuje dane szybkiej akcji. */
    public function update(Request $request, QuickAction $quickAction)
    {
        $quickAction->update($this->validated($request));

        return redirect()->route('admin.szybkie-akcje.index')->with('status', 'Szybka akcja została zaktualizowana.');
    }

    /**
     * Zapisuje układ z wizualnego edytora siatki: kolejność, szerokość (kolumny), tryb „pasek" i „negatyw".
     * Przyjmuje tylko akcje bieżącej witryny.
     */
    public function layout(Request $request)
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'max:60'],
            'items.*.id' => ['required', 'integer'],
            'items.*.cols' => ['required', 'integer', 'in:1,2,3'],
            'items.*.strip' => ['required', 'boolean'],
            'items.*.is_negative' => ['required', 'boolean'],
        ]);

        $actions = QuickAction::forCurrentSite()->whereIn('id', collect($data['items'])->pluck('id'))->get()->keyBy('id');

        foreach (array_values($data['items']) as $position => $item) {
            $actions->get($item['id'])?->update([
                'order' => $position,
                'cols' => (int) $item['cols'],
                'strip' => (bool) $item['strip'],
                'is_negative' => (bool) $item['is_negative'],
            ]);
        }

        return response()->json(['ok' => true]);
    }

    /** Usuwa szybką akcję. */
    public function destroy(QuickAction $quickAction)
    {
        $quickAction->delete();

        return redirect()->route('admin.szybkie-akcje.index')->with('status', 'Szybka akcja została usunięta.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:140'],
            'icon' => ['required', 'string', 'max:100'],
            'url' => ['required', 'string', 'max:255'],
            'order' => ['nullable', 'integer', 'min:0'],
            'color'       => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'is_negative' => ['nullable', 'boolean'],
            'cols'        => ['nullable', 'integer', 'in:1,2,3'],
            'strip'       => ['nullable', 'boolean'],
        ]);

        // Kolumna opisu pojawia się po migracji — do tego czasu zapis nie może się wywrócić.
        if (! Schema::hasColumn('quick_actions', 'description')) {
            unset($data['description']);
        }

        $data['order']       = $data['order'] ?? 0;
        $data['is_negative'] = (bool) ($data['is_negative'] ?? false);
        $data['cols']        = (int) ($data['cols'] ?? 1);
        $data['strip']       = (bool) ($data['strip'] ?? false);

        return $data;
    }
}
