<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuickAction;
use Illuminate\Http\Request;

class QuickActionController extends Controller
{
    public function index()
    {
        $quickActions = QuickAction::orderBy('order')->get();

        return view('admin.quick-actions.index', compact('quickActions'));
    }

    public function create()
    {
        return view('admin.quick-actions.form', ['quickAction' => new QuickAction]);
    }

    public function store(Request $request)
    {
        QuickAction::create($this->validated($request));

        return redirect()->route('admin.szybkie-akcje.index')->with('status', 'Szybka akcja została dodana.');
    }

    public function edit(QuickAction $quickAction)
    {
        return view('admin.quick-actions.form', compact('quickAction'));
    }

    public function update(Request $request, QuickAction $quickAction)
    {
        $quickAction->update($this->validated($request));

        return redirect()->route('admin.szybkie-akcje.index')->with('status', 'Szybka akcja została zaktualizowana.');
    }

    public function destroy(QuickAction $quickAction)
    {
        $quickAction->delete();

        return redirect()->route('admin.szybkie-akcje.index')->with('status', 'Szybka akcja została usunięta.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'icon' => ['required', 'string', 'max:100'],
            'url' => ['required', 'string', 'max:255'],
            'order' => ['nullable', 'integer', 'min:0'],
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $data['order'] = $data['order'] ?? 0;

        return $data;
    }
}
