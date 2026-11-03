@extends('admin.layout')

@section('title', 'Podgląd menu roli')

@section('content')
    @php
        $field = 'min-h-11 w-full rounded border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark';
        $btn = 'inline-flex min-h-11 items-center gap-2 rounded px-4 text-sm font-bold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2';
    @endphp

    <p class="mb-4 max-w-3xl text-sm text-ink">
        Zobacz, jakie menu panelu ma rola, grupa edytora albo konkretna osoba, bez logowania się na jej konto.
        To tylko podgląd: niczego nie zmienia. Menu zależy też od modułów włączonych w witrynie „{{ $siteName }}”,
        nad którą teraz pracujesz.
    </p>

    <form method="GET" action="{{ route('admin.uzytkownicy.podglad-menu') }}" class="mb-6 flex flex-wrap items-end gap-3 rounded-md bg-white p-4">
        <div class="min-w-48 flex-1">
            <label for="jako" class="mb-1 block text-sm font-bold text-ink">Pokaż menu dla</label>
            <select id="jako" name="jako" class="{{ $field }}">
                <option value="">Wybierz rolę, grupę albo użytkownika</option>
                @foreach ($options as $heading => $entries)
                    <optgroup label="{{ $heading }}">
                        @foreach ($entries as $entry)
                            <option value="{{ $entry['value'] }}" @selected($choice === $entry['value'])>{{ $entry['label'] }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </div>
        <button type="submit" class="{{ $btn }} bg-brand-dark text-white hover:bg-ink">Pokaż menu</button>
        <a href="{{ route('admin.uzytkownicy.uprawnienia') }}" class="{{ $btn }} bg-gray-100 text-ink hover:bg-brand-light">
            <i class="fa-solid fa-table-cells" aria-hidden="true"></i> Macierz uprawnień
        </a>
    </form>

    @if ($preview)
        @php
            $groupCount = count($preview['groups']);
            $hiddenCount = $preview['total'] - $preview['visible'];
        @endphp

        <section aria-labelledby="h-podglad" class="space-y-6">
            <div class="rounded-md bg-white p-6">
                <h2 id="h-podglad" class="text-lg font-bold text-ink">Menu: {{ $title }}</h2>
                <p class="mt-1 text-sm text-ink">
                    Widzi {{ $preview['visible'] }} z {{ $preview['total'] }} pozycji menu w {{ $groupCount }} {{ $groupCount === 1 ? 'grupie' : 'grupach' }}.
                </p>
                @if ($subject->exists)
                    <p class="mt-2 text-sm text-ink">
                        <a href="{{ route('admin.uzytkownicy.edit', $subject) }}" class="font-bold text-brand-dark underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">Edytuj użytkownika {{ $subject->name }}</a>
                    </p>
                @elseif ($members->isNotEmpty())
                    <p class="mt-2 text-sm text-ink">
                        Osoby z tą rolą:
                        @foreach ($members as $member)
                            <a href="{{ route('admin.uzytkownicy.podglad-menu', ['jako' => 'uzytkownik:'.$member->id]) }}" class="font-bold text-brand-dark underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">{{ $member->name }}</a>@if (! $loop->last), @endif
                        @endforeach
                    </p>
                @else
                    <p class="mt-2 text-sm text-ink">Nikt nie ma teraz tej roli.</p>
                @endif
            </div>

            <div class="rounded-md bg-white p-6">
                <h3 class="text-base font-bold text-ink">Co widzi</h3>
                <p class="mt-1 text-sm text-ink">Grupy w takiej kolejności, w jakiej są w panelu tej osoby.</p>

                <div class="mt-4 divide-y divide-gray-100">
                    @foreach ($preview['groups'] as $group)
                        <section aria-labelledby="podglad-grupa-{{ $group['key'] }}" class="py-4 first:pt-0 last:pb-0">
                            <h4 id="podglad-grupa-{{ $group['key'] }}" class="flex flex-wrap items-center gap-2 text-sm font-bold text-ink">
                                <i class="fa-solid {{ $group['icon'] }} w-5 text-center text-brand-dark" aria-hidden="true"></i>{{ $group['label'] }}
                                @if ($group['focus'])
                                    <span class="font-bold text-brand-dark">· obszar roboczy tej roli</span>
                                @endif
                            </h4>
                            @foreach ($group['blocks'] as $block)
                                @if ($block['heading'])
                                    <p class="mt-3 text-xs font-bold uppercase tracking-wide text-muted">{{ $block['heading'] }}</p>
                                @endif
                                <ul class="mt-1 grid gap-x-6 gap-y-1 sm:grid-cols-2 lg:grid-cols-3" role="list">
                                    @foreach ($block['items'] as $item)
                                        <li class="flex items-start gap-2 py-1 text-sm text-ink">
                                            <i class="fa-solid {{ $item['icon'] }} mt-0.5 w-4 flex-none text-center text-xs text-muted" aria-hidden="true"></i>
                                            <span>{{ $item['label'] }}@if ($item['children'])<span class="block text-xs text-muted">{{ implode(', ', $item['children']) }}</span>@endif</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endforeach
                        </section>
                    @endforeach
                </div>
            </div>

            <div class="rounded-md bg-white p-6">
                @if ($hiddenCount > 0)
                    <details>
                        <summary class="flex min-h-11 cursor-pointer items-center text-base font-bold text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                            Czego nie widzi ({{ $hiddenCount }})
                        </summary>
                        <p class="mt-1 text-sm text-ink">Pozycje, które widzi administrator, a ta osoba nie: rola nie ma do nich dostępu albo moduł jest wyłączony w tej witrynie.</p>
                        <div class="mt-4 divide-y divide-gray-100">
                            @foreach ($preview['hidden'] as $hidden)
                                <section class="py-3 first:pt-0 last:pb-0">
                                    <h4 class="text-sm font-bold text-ink">{{ $hidden['label'] }}</h4>
                                    <p class="mt-1 text-sm text-ink">{{ implode(', ', $hidden['items']) }}</p>
                                </section>
                            @endforeach
                        </div>
                    </details>
                @else
                    <h3 class="text-base font-bold text-ink">Czego nie widzi</h3>
                    <p class="mt-1 text-sm text-ink">Niczego: ta osoba widzi całe menu.</p>
                @endif
            </div>
        </section>
    @else
        <div class="rounded-md bg-white px-6 py-10 text-center">
            <p class="font-bold text-ink">Wybierz, czyje menu chcesz zobaczyć</p>
            <p class="mx-auto mt-1 max-w-md text-sm text-ink">
                Rola pokazuje menu każdego, kto ją ma. Grupa edytora to moduły zaznaczone w grupie. Użytkownik to jego własne menu.
            </p>
        </div>
    @endif
@endsection
