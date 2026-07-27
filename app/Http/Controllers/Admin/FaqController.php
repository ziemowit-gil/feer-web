<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\FaqRequest;
use App\Models\Faq;

class FaqController extends Controller
{
    public function index()
    {
        $faqs = Faq::orderBy('order')->orderBy('id')->get();

        return view('admin.faqs.index', compact('faqs'));
    }

    public function create()
    {
        return view('admin.faqs.form', ['faq' => new Faq(['is_published' => true])]);
    }

    public function store(FaqRequest $request)
    {
        Faq::create($this->prepared($request));

        return redirect()->route('admin.faq.index')->with('status', 'Pytanie zostało dodane.');
    }

    public function edit(Faq $faq)
    {
        return view('admin.faqs.form', compact('faq'));
    }

    public function update(FaqRequest $request, Faq $faq)
    {
        $faq->update($this->prepared($request));

        return redirect()->route('admin.faq.index')->with('status', 'Pytanie zostało zaktualizowane.');
    }

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
