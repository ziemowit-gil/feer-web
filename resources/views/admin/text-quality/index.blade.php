@extends('admin.layout')

@section('title', 'Jakość tekstów')

@section('content')
    @php
        $link = fn (array $q) => route('admin.jakosc-tekstow.index', array_filter($q, fn ($v) => $v !== null && $v !== ''));
        $chip = 'inline-flex min-h-10 items-center rounded-md px-4 text-sm font-bold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
        $on = 'bg-brand-dark text-white';
        $off = 'bg-gray-100 text-ink hover:bg-brand-light';
        $counts = $result['counts'];
        $sumAll = array_sum($counts);
    @endphp

    <div class="max-w-6xl space-y-8">
        <div>
            <h1 class="text-2xl font-bold text-ink">Jakość tekstów</h1>
            <p class="mt-2 max-w-3xl text-sm text-ink">
                Przegląd opublikowanych treści (strony, aktualności, działania, FAQ) pod kątem wzorców, które najczęściej zdradzają
                tekst napisany „na autopilocie” albo niedokończony. Skaner tylko wskazuje miejsca. Niczego nie zmienia, poprawki robisz w edytorze.
                Przeskanowano: <strong>{{ $result['scanned'] }}</strong> treści, znaleziono <strong>{{ $sumAll }}</strong> miejsc.
            </p>
        </div>

        {{-- Reguły: liczba trafień i filtr --}}
        <section aria-labelledby="tq-rules">
            <h2 id="tq-rules" class="mb-3 text-lg font-bold text-ink">Co sprawdzamy</h2>
            <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3" role="list">
                @foreach ($rules as $key => $r)
                    <li>
                        <a href="{{ $link(['regula' => $rule === $key ? null : $key, 'zrodlo' => $source]) }}"
                           @if ($rule === $key) aria-current="true" @endif
                           class="flex h-full flex-col rounded-lg p-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark {{ $rule === $key ? 'bg-brand-light' : 'bg-gray-50 hover:bg-brand-light' }}">
                            <span class="flex items-baseline justify-between gap-3">
                                <span class="font-bold text-ink">{{ $r['label'] }}</span>
                                <span class="text-2xl font-extrabold text-ink" aria-label="{{ $counts[$key] }} trafień">{{ $counts[$key] }}</span>
                            </span>
                            <span class="mt-1 text-sm text-ink">{{ $r['hint'] }}</span>
                            <span class="mt-2 text-sm font-bold text-brand-dark underline underline-offset-4">{{ $rule === $key ? 'Pokaż wszystkie reguły' : 'Pokaż tylko te' }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>

        {{-- Filtr źródła --}}
        <nav aria-label="Filtr według rodzaju treści" class="flex flex-wrap items-center gap-2">
            <span class="mr-1 text-sm font-bold text-ink">Treści:</span>
            <a href="{{ $link(['regula' => $rule]) }}" class="{{ $chip }} {{ $source === null ? $on : $off }}" @if ($source === null) aria-current="true" @endif>Wszystkie</a>
            @foreach ($sources as $key => $label)
                <a href="{{ $link(['regula' => $rule, 'zrodlo' => $key]) }}" class="{{ $chip }} {{ $source === $key ? $on : $off }}" @if ($source === $key) aria-current="true" @endif>{{ $label }}</a>
            @endforeach
        </nav>

        <section aria-labelledby="tq-findings">
            <h2 id="tq-findings" class="mb-3 text-lg font-bold text-ink">
                Miejsca do poprawy
                <span class="font-normal text-ink">({{ $result['total'] }}@if ($result['total'] > $result['findings']->count()), pokazano {{ $result['findings']->count() }}@endif)</span>
            </h2>

            @if ($result['findings']->isEmpty())
                <p class="rounded-md bg-gray-50 p-5 text-ink" role="status">Nic nie znaleziono dla wybranego filtra. Tak ma być.</p>
            @else
                <div class="overflow-x-auto rounded-md bg-white">
                    <table class="w-full text-left text-sm text-ink">
                        <caption class="sr-only">Miejsca w treściach, które warto poprawić</caption>
                        <thead class="bg-gray-50 text-xs font-bold uppercase tracking-widest">
                            <tr>
                                <th scope="col" class="px-4 py-3">Treść</th>
                                <th scope="col" class="px-4 py-3">Co znaleziono</th>
                                <th scope="col" class="px-4 py-3">Fragment</th>
                                <th scope="col" class="px-4 py-3"><span class="sr-only">Akcja</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($result['findings'] as $f)
                                <tr class="align-top odd:bg-white even:bg-gray-50">
                                    <th scope="row" class="px-4 py-3 font-bold">
                                        {{ $f['title'] !== '' ? \Illuminate\Support\Str::limit($f['title'], 60) : '(bez tytułu)' }}
                                        <span class="block text-xs font-normal">{{ $f['source_label'] }}, pole: {{ $f['field'] }}</span>
                                    </th>
                                    <td class="px-4 py-3">{{ $rules[$f['rule']]['label'] }}</td>
                                    <td class="px-4 py-3">
                                        {{ $f['before'] }}<mark class="rounded bg-brand-light px-0.5 font-bold text-ink underline decoration-brand-dark decoration-2 underline-offset-2">{{ $f['match'] }}</mark>{{ $f['after'] }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <a href="{{ $f['edit_url'] }}" class="inline-flex min-h-11 items-center font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                                            Edytuj<span class="sr-only">: {{ $f['title'] }}</span>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <p class="max-w-3xl text-sm text-ink">
            Skaner szuka wzorców, a nie ocenia sensu. Część trafień jest zasadna (nazwa własna z myślnikiem, zakres godzin, cytat). Takie zostaw bez zmian.
            Archiwum aktualności pokazuje te same reguły co reszta serwisu, więc wiele starszych wpisów będzie miało trafienia.
        </p>
    </div>
@endsection
