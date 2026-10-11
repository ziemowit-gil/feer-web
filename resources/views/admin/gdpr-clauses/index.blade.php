@extends('admin.layout')

@section('title', 'Klauzule RODO')

@section('content')
    <div class="max-w-5xl space-y-6">
        <p class="text-sm text-muted">
            Klauzule informacyjne prowadzi się w systemie SZO (moduł „Klauzule RODO”). Tutaj są ich kopie
            do pokazania na stronie — import działa automatycznie raz dziennie, a przyciskiem poniżej od razu.
            Aby wyświetlić listę na stronie (np. <a href="{{ url('/rodo') }}" class="font-bold text-brand-dark underline">/rodo</a>),
            wstaw w treść strony shortcode <code class="rounded bg-gray-100 px-1">[klauzule-rodo]</code>
            (wersje angielskie: <code class="rounded bg-gray-100 px-1">[klauzule-rodo:en]</code>).
        </p>

        {{-- Import --}}
        <div class="flex flex-wrap items-center gap-4 p-4 rounded-md bg-white">
            <form method="POST" action="{{ route('admin.klauzule-rodo.import') }}">
                @csrf
                <button type="submit" @disabled($szoUrl === '')
                    class="inline-flex items-center gap-2 rounded bg-brand-dark px-5 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                    <i class="fa-solid fa-cloud-arrow-down" aria-hidden="true"></i> Importuj teraz z SZO
                </button>
            </form>
            <div class="text-sm text-muted">
                @if ($szoUrl === '')
                    <span class="font-bold text-red-700">Brak adresu SZO — wpisz go w <a href="{{ route('admin.ustawienia.edit', ['tab' => 'login']) }}" class="underline">Ustawienia → Logowanie i integracje → SZO</a> (lub ustaw <code>SZO_URL</code> w pliku .env).</span>
                @else
                    Źródło: <a href="{{ $szoUrl }}{{ \App\Services\SzoClient::CLAUSES_DIRECT_PATH }}" class="font-mono text-brand-dark underline" target="_blank" rel="noopener">{{ $szoUrl }}/klauzule.json<span class="sr-only"> (otwiera się w nowej karcie)</span></a>
                @endif
                <br>
                @if ($lastRun)
                    Ostatni import: <time datetime="{{ $lastRun['at'] }}">{{ \Illuminate\Support\Carbon::parse($lastRun['at'])->format('d.m.Y H:i') }}</time>
                    — {{ $lastRun['stats']['added'] ?? 0 }} nowych, {{ $lastRun['stats']['updated'] ?? 0 }} zaktualizowanych, {{ $lastRun['stats']['removed'] ?? 0 }} usuniętych
                @else
                    Jeszcze nie importowano.
                @endif
            </div>
        </div>

        {{-- Lista --}}
        @if ($clauses->isEmpty())
            <div class="rounded-lg border border-dashed border-gray-300 bg-white p-8 text-center text-sm text-muted">
                Brak klauzul. Opublikuj klauzule w SZO i kliknij „Importuj teraz z SZO”.
            </div>
        @else
            <form method="POST" action="{{ route('admin.klauzule-rodo.update') }}" class="space-y-3">
                @csrf
                @method('PUT')
                <div class="overflow-x-auto rounded-md bg-white">
                    <table class="w-full text-left text-sm">
                        <caption class="sr-only">Klauzule RODO zaimportowane z SZO</caption>
                        <thead class="bg-gray-100 text-xs font-bold uppercase text-muted">
                            <tr>
                                <th scope="col" class="px-4 py-3">Klauzula</th>
                                <th scope="col" class="px-4 py-3">Język</th>
                                <th scope="col" class="px-4 py-3">Wersja / zmiana w SZO</th>
                                <th scope="col" class="px-4 py-3">Kolejność</th>
                                <th scope="col" class="px-4 py-3">Na stronie</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($clauses as $c)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-ink">{{ $c->title }}</div>
                                        <div class="font-mono text-xs text-muted">{{ $c->slug }}</div>
                                        <div class="mt-1 flex flex-wrap gap-3 text-xs">
                                            @if ($c->url)<a href="{{ $c->url }}" target="_blank" rel="noopener" class="text-brand-dark underline">Podgląd w SZO<span class="sr-only"> — {{ $c->title }} (otwiera się w nowej karcie)</span></a>@endif
                                            @if ($c->pdf_url)<a href="{{ $c->pdf_url }}" class="text-brand-dark underline">PDF<span class="sr-only"> — {{ $c->title }}</span></a>@endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 uppercase">{{ $c->lang }}</td>
                                    <td class="px-4 py-3 text-muted">
                                        {{ $c->version ? 'v' . $c->version : '—' }}
                                        @if ($c->remote_updated_at) · {{ $c->remote_updated_at->format('d.m.Y') }} @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <label for="sort-{{ $c->id }}" class="sr-only">Kolejność — {{ $c->title }}</label>
                                        <input type="number" id="sort-{{ $c->id }}" name="clauses[{{ $c->id }}][sort_order]" value="{{ $c->sort_order }}" min="0" max="9999"
                                            class="w-20 rounded border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="hidden" name="clauses[{{ $c->id }}][is_visible]" value="0">
                                        <label class="inline-flex items-center gap-2">
                                            <input type="checkbox" name="clauses[{{ $c->id }}][is_visible]" value="1" @checked($c->is_visible)
                                                class="rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                                            <span>Pokazuj<span class="sr-only"> — {{ $c->title }}</span></span>
                                        </label>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="rounded bg-brand-dark px-5 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">Zapisz widoczność i kolejność</button>
                </div>
            </form>
        @endif
    </div>
@endsection
