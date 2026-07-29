<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LandingPageRequest;
use App\Models\LandingPage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LandingPageController extends Controller
{
    public function index()
    {
        $pages = LandingPage::withCount('registrations')->orderByDesc('updated_at')->get();

        return view('admin.landing-pages.index', compact('pages'));
    }

    public function create()
    {
        return view('admin.landing-pages.form', ['page' => new LandingPage]);
    }

    public function store(LandingPageRequest $request)
    {
        LandingPage::create($this->prepared($request));

        return redirect()->route('admin.lp.index')->with('status', 'Landing page został utworzony.');
    }

    public function edit(LandingPage $landing)
    {
        return view('admin.landing-pages.form', ['page' => $landing]);
    }

    public function update(LandingPageRequest $request, LandingPage $landing)
    {
        $landing->update($this->prepared($request));

        return redirect()->route('admin.lp.index')->with('status', 'Landing page został zapisany.');
    }

    public function destroy(LandingPage $landing)
    {
        $landing->delete();

        return redirect()->route('admin.lp.index')->with('status', 'Landing page został usunięty.');
    }

    public function registrations(LandingPage $landing)
    {
        return view('admin.landing-pages.registrations', [
            'page' => $landing,
            'registrations' => $landing->registrations()->latest()->get(),
        ]);
    }

    /** Eksport zapisów do CSV (z dodatkowymi polami zdefiniowanymi dla strony). */
    public function exportRegistrations(LandingPage $landing): StreamedResponse
    {
        $fields = $landing->formFields();
        $filename = 'zapisy-'.$landing->slug.'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($landing, $fields) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF"); // BOM — poprawne polskie znaki w Excelu

            $header = ['imię i nazwisko', 'email', 'telefon', 'zgoda', 'data zapisu'];
            foreach ($fields as $f) {
                $header[] = $f['label'];
            }
            fputcsv($handle, $header);

            $landing->registrations()->orderBy('created_at')->chunk(200, function ($chunk) use ($handle, $fields) {
                foreach ($chunk as $r) {
                    $row = [
                        $r->name,
                        $r->email,
                        $r->phone,
                        $r->consent ? 'tak' : 'nie',
                        $r->created_at->format('Y-m-d H:i'),
                    ];
                    foreach ($fields as $f) {
                        $row[] = $r->extra[$f['key']] ?? '';
                    }
                    fputcsv($handle, $row);
                }
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Normalizuje dane: czyści puste wiersze repeaterów i booleany. */
    private function prepared(LandingPageRequest $request): array
    {
        $data = $request->validated();
        $data['is_published'] = $request->boolean('is_published');

        foreach (['speakers', 'benefits', 'agenda'] as $section) {
            $data[$section] = collect($data[$section] ?? [])
                ->map(fn ($row) => array_map(fn ($v) => is_string($v) ? trim($v) : $v, $row))
                ->filter(fn ($row) => collect($row)->filter()->isNotEmpty())
                ->values()
                ->all();
        }

        $data['section_order'] = array_values($data['section_order'] ?? []);
        $data['form_fields'] = $this->normalizeFields($data['form_fields'] ?? []);

        return $data;
    }

    /**
     * Nadaje każdemu dodatkowemu polu stabilny, unikalny klucz (ze slug etykiety)
     * i zamienia listę opcji z tekstu (po przecinku) na tablicę.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function normalizeFields(array $rows): array
    {
        $used = [];

        return collect($rows)
            ->filter(fn ($f) => filled($f['label'] ?? null))
            ->map(function ($f) use (&$used) {
                $key = Str::slug($f['label'], '_') ?: 'pole';
                $base = $key;
                $n = 1;
                while (in_array($key, $used, true)) {
                    $key = $base.'_'.(++$n);
                }
                $used[] = $key;

                $type = in_array($f['type'] ?? '', array_keys(LandingPage::FIELD_TYPES), true) ? $f['type'] : 'text';
                $options = $type === 'select'
                    ? array_values(array_filter(array_map('trim', explode(',', (string) ($f['options'] ?? '')))))
                    : [];

                return [
                    'key' => $key,
                    'label' => trim($f['label']),
                    'type' => $type,
                    'required' => ! empty($f['required']),
                    'options' => $options,
                ];
            })
            ->values()
            ->all();
    }
}
