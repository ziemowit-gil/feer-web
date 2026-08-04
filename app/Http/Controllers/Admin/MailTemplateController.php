<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MailTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MailTemplateController extends Controller
{
    public function index(): View
    {
        $templates = MailTemplate::orderBy('name')->get();

        return view('admin.mail-templates.index', compact('templates'));
    }

    public function edit(MailTemplate $mailTemplate): View
    {
        return view('admin.mail-templates.edit', ['template' => $mailTemplate]);
    }

    public function update(Request $request, MailTemplate $mailTemplate): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body'    => ['required', 'string'],
        ]);

        $mailTemplate->update($validated);

        return redirect()->route('admin.mail-templates.index')
            ->with('success', 'Szablon „' . $mailTemplate->name . '" został zaktualizowany.');
    }
}
