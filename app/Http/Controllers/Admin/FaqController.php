<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\FaqRequest;
use App\Models\Faq;

/**
 * Panel admin: CRUD pytań i odpowiedzi (FAQ) z ręcznym zarządzaniem kolejnością.
 *
 * Metody: index(), create(), store(), edit(), update(), destroy().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class FaqController extends Controller
{
    /** Wyświetla listę pytań FAQ z uwzględnieniem kolejności. */
    public function index()
    {
        $faqs = Faq::orderBy('order')->orderBy('id')->get();

        return view('admin.faqs.index', compact('faqs'));
    }

    /** Wyświetla formularz dodawania nowego pytania FAQ. */
    public function create()
    {
        return view('admin.faqs.form', ['faq' => new Faq(['is_published' => true])]);
    }

    /** Zapisuje nowe pytanie FAQ. */
    public function store(FaqRequest $request)
    {
        Faq::create($this->prepared($request));

        return redirect()->route('admin.faq.index')->with('status', 'Pytanie zostało dodane.');
    }

    /** Wyświetla formularz edycji pytania FAQ. */
    public function edit(Faq $faq)
    {
        return view('admin.faqs.form', compact('faq'));
    }

    /** Aktualizuje pytanie FAQ. */
    public function update(FaqRequest $request, Faq $faq)
    {
        $faq->update($this->prepared($request));

        return redirect()->route('admin.faq.index')->with('status', 'Pytanie zostało zaktualizowane.');
    }

    /** Usuwa pytanie FAQ. */
    public function destroy(Faq $faq)
    {
        $faq->delete();

        return redirect()->route('admin.faq.index')->with('status', 'Pytanie zostało usunięte.');
    }

    private function prepared(FaqRequest $request): array
    {
        $data = $request->validated();
        $data['is_published'] = $request->boolean('is_published');
        $data['order'] = $data['order'] ?? 0;

        return $data;
    }
}
