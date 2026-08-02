<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Poll;
use App\Models\PollOption;
use Illuminate\Http\Request;

/**
 * Panel admin: CRUD ankiet z zarządzaniem opcjami głosowania.
 *
 * Metody: index(), create(), store(), edit(), update(), destroy().
 *
 * @author Ziemowit Gil <ziemowit.gil@feer.org.pl>
 */
class PollController extends Controller
{
    /** Wyświetla listę ankiet z opcjami głosowania. */
    public function index()
    {
        $polls = Poll::with('options')->latest()->get();

        return view('admin.polls.index', compact('polls'));
    }

    /** Wyświetla formularz tworzenia nowej ankiety. */
    public function create()
    {
        return view('admin.polls.form', ['poll' => new Poll]);
    }

    /** Zapisuje nową ankietę z opcjami głosowania. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:255'],
            'options' => ['required', 'array', 'min:2'],
            'options.*.label' => ['required', 'string', 'max:255'],
        ]);

        $poll = Poll::create([
            'question' => $data['question'],
            'is_active' => $request->boolean('is_active'),
        ]);

        foreach ($data['options'] as $i => $option) {
            $poll->options()->create(['label' => $option['label'], 'order' => $i]);
        }

        return redirect()->route('admin.ankiety.index')->with('status', 'Ankieta została utworzona.');
    }

    /** Wyświetla formularz edycji ankiety z aktualnymi opcjami. */
    public function edit(Poll $poll)
    {
        return view('admin.polls.form', ['poll' => $poll->load('options')]);
    }

    /** Aktualizuje pytanie, opcje i status ankiety (dodaje/usuwa/edytuje opcje). */
    public function update(Request $request, Poll $poll)
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:255'],
            'options' => ['array'],
            'options.*.id' => ['nullable', 'exists:poll_options,id'],
            'options.*.label' => ['nullable', 'string', 'max:255'],
            'options.*.delete' => ['sometimes'],
        ]);

        $poll->update([
            'question' => $data['question'],
            'is_active' => $request->boolean('is_active'),
        ]);

        foreach ($data['options'] ?? [] as $i => $option) {
            $shouldDelete = ! empty($option['delete']);
            $label = trim($option['label'] ?? '');

            if (! empty($option['id'])) {
                $existing = PollOption::where('poll_id', $poll->id)->find($option['id']);

                if (! $existing) {
                    continue;
                }

                if ($shouldDelete) {
                    $existing->delete();
                } elseif ($label !== '') {
                    $existing->update(['label' => $label, 'order' => $i]);
                }
            } elseif ($label !== '' && ! $shouldDelete) {
                $poll->options()->create(['label' => $label, 'order' => $i]);
            }
        }

        return redirect()->route('admin.ankiety.index')->with('status', 'Ankieta została zaktualizowana.');
    }

    /** Usuwa ankietę wraz z opcjami i oddanymi głosami. */
    public function destroy(Poll $poll)
    {
        $poll->delete();

        return redirect()->route('admin.ankiety.index')->with('status', 'Ankieta została usunięta.');
    }
}
