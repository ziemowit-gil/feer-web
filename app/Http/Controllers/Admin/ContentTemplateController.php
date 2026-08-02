<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentTemplate;
use Illuminate\Http\Request;

/**
 * Panel admin: szablony treści wielokrotnego użycia w formularzach (JSON API dla edytora).
 *
 * Metody: index(), manage(), load(), store(), destroy().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class ContentTemplateController extends Controller
{
    /** Listowanie szablonów danego typu — zwraca JSON (dla JS w formularzu). */
    public function index(Request $request)
    {
        $type = $request->query('type');

        $templates = ContentTemplate::when($type, fn ($q) => $q->where('type', $type))
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        return response()->json($templates);
    }

    /** Strona zarządzania szablonami (podgląd + usuwanie). */
    public function manage()
    {
        $templates = ContentTemplate::orderBy('type')->orderBy('name')->get();

        return view('admin.content-templates.index', compact('templates'));
    }

    /** Załaduj dane szablonu jako JSON (wywoływane przez JS w formularzu). */
    public function load(ContentTemplate $template)
    {
        return response()->json($template->data);
    }

    /** Zapisz nowy szablon z danych przesłanych przez formularz. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'string', 'in:news,event,volunteer_ad'],
            'name' => ['required', 'string', 'max:120'],
            'data' => ['present', 'array'],
        ]);

        ContentTemplate::create($data);

        $savedName = $data['name'];

        return response()->json(['status' => "Szablon \u{201E}{$savedName}\u{201D} został zapisany."]);
    }

    /** Usuń szablon. */
    public function destroy(Request $request, ContentTemplate $template)
    {
        $name = $template->name;
        $template->delete();

        if ($request->expectsJson()) {
            return response()->json(['status' => "Szablon \u{201E}{$name}\u{201D} został usunięty."]);
        }

        return redirect()->back()->with('status', "Szablon \u{201E}{$name}\u{201D} został usunięty.");
    }
}
