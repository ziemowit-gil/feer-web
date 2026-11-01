@extends('admin.layout')

@section('title', 'Zarządzaj subwitrynami')

@section('content')
    @php
        $btn = 'inline-flex min-h-11 items-center gap-2 rounded px-4 text-sm font-bold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2';
        $btnPlain = $btn.' bg-gray-100 text-ink hover:bg-brand-light';
        $renameRoute = Route::has('admin.witryny.nazwa');
    @endphp

    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <p class="max-w-2xl text-sm text-ink">
            Subwitryna ma własną nazwę, adres, wygląd i treść (np. ośrodek albo osobny BIP), a korzysta z tego samego panelu.
            Tutaj zakładasz je, zmieniasz ich nazwę i adres oraz usuwasz. Żeby edytować treść i ustawienia subwitryny,
            kliknij „Przełącz się”.
        </p>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.witryny.create') }}" class="{{ $btn }} bg-brand-dark text-white hover:bg-ink">
                <i class="fa-solid fa-plus" aria-hidden="true"></i> Nowa subwitryna
            </a>
            <a href="{{ route('admin.wybor-witryny') }}" class="{{ $btnPlain }}">
                <i class="fa-solid fa-table-cells-large" aria-hidden="true"></i> Ekran wyboru
            </a>
            <a href="{{ route('admin.witryny.ustawienia') }}" class="{{ $btnPlain }}">
                <i class="fa-solid fa-right-left" aria-hidden="true"></i> Przenieś ustawienia
            </a>
        </div>
    </div>

    {{-- Witryna główna: jej adres to adres serwera (APP_URL), więc tu można zmienić tylko nazwę. --}}
    <section aria-labelledby="h-main-site">
        <h2 id="h-main-site" class="mb-2 text-base font-bold text-ink">Witryna główna</h2>
        <ul class="space-y-2" role="list">
            @foreach ($mains as $main)
                <li class="flex flex-wrap items-center gap-3 rounded-md bg-white px-4 py-3">
                    @if ($main->logoUrl())
                        <img src="{{ $main->logoUrl() }}" alt="" class="h-10 w-10 flex-none rounded object-contain">
                    @else
                        <span class="flex h-10 w-10 flex-none items-center justify-center rounded bg-brand-dark text-sm font-bold text-white" aria-hidden="true">{{ mb_substr($main->site_name, 0, 1) }}</span>
                    @endif
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-bold text-ink">{{ $main->site_name }}</p>
                        <p class="truncate text-sm text-muted">{{ $main->domain ?: url('/') }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if ($renameRoute)
                            <a href="{{ route('admin.witryny.nazwa', $main) }}" class="{{ $btnPlain }}">
                                <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Zmień nazwę<span class="sr-only"> witryny {{ $main->site_name }}</span>
                            </a>
                        @endif
                        <form method="POST" action="{{ route('admin.witryny.przelacz', $main) }}">
                            @csrf
                            <button type="submit" class="{{ $btnPlain }}">Przełącz się<span class="sr-only"> na witrynę {{ $main->site_name }}</span></button>
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>
    </section>

    <section aria-labelledby="h-subsites" class="mt-8">
        <h2 id="h-subsites" class="text-base font-bold text-ink">Subwitryny ({{ count($rows) }})</h2>

        @if ($rows === [])
            <div class="mt-3 rounded-md bg-white px-6 py-10 text-center">
                <p class="font-bold text-ink">Nie ma jeszcze subwitryn</p>
                <p class="mx-auto mt-1 max-w-md text-sm text-ink">Subwitryna to np. ośrodek prowadzony przez organizację albo osobny Biuletyn Informacji Publicznej.</p>
                <a href="{{ route('admin.witryny.create') }}" class="{{ $btn }} mt-4 bg-brand-dark text-white hover:bg-ink">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i> Nowa subwitryna
                </a>
            </div>
        @else
            <div class="mt-3 overflow-x-auto rounded-md bg-white">
                <table class="w-full min-w-[44rem] text-left text-sm">
                    <caption class="sr-only">Subwitryny sieci: nazwa, adres, szablon, status i działania</caption>
                    <thead class="bg-gray-50 text-ink">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-bold">Subwitryna</th>
                            <th scope="col" class="px-4 py-3 font-bold">Adres</th>
                            <th scope="col" class="hidden px-4 py-3 font-bold xl:table-cell">Szablon</th>
                            <th scope="col" class="px-4 py-3 font-bold">Status</th>
                            <th scope="col" class="px-4 py-3 text-right font-bold"><span class="sr-only">Działania</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($rows as $row)
                            @php
                                $sub = $row['site'];
                                $url = ($sub->domain || $sub->slug) ? current_site_url($sub) : null;
                                $status = ! $url ? ['fa-link-slash', 'Bez adresu'] : ($sub->under_construction ? ['fa-hammer', 'Dopiero tworzymy'] : ['fa-circle-check', 'Publiczna']);
                            @endphp
                            <tr class="align-middle hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3" @if ($row['depth'] > 0) style="padding-left: {{ $row['depth'] * 1.5 }}rem" @endif>
                                        @if ($sub->logoUrl())
                                            <img src="{{ $sub->logoUrl() }}" alt="" class="h-9 w-9 flex-none rounded object-contain">
                                        @else
                                            <span class="flex h-9 w-9 flex-none items-center justify-center rounded bg-gray-200 text-sm font-bold text-ink" aria-hidden="true">{{ mb_substr($sub->site_name, 0, 1) }}</span>
                                        @endif
                                        <div class="min-w-0">
                                            <p class="font-bold text-ink">{{ $sub->site_name }}@if ($sub->isBipSite()) <span class="ml-1 text-xs font-bold text-muted">· BIP</span>@endif</p>
                                            @if ($row['depth'] > 0)<p class="text-xs text-muted">w: {{ $row['parent']->site_name }}</p>@endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($url)
                                        <a href="{{ $url }}" target="_blank" rel="noopener" class="font-bold text-brand-dark underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">{{ preg_replace('#^https?://#', '', $url) }}<span class="sr-only"> (otwiera się w nowej karcie)</span></a>
                                        <p class="text-xs text-muted">{{ $sub->domain ? 'własna domena' : 'adres w serwisie' }}</p>
                                    @else
                                        <span class="text-muted">Brak adresu — witryna niedostępna publicznie</span>
                                    @endif
                                </td>
                                <td class="hidden px-4 py-3 text-ink xl:table-cell">{{ \Illuminate\Support\Str::before(\App\Models\SiteSetting::SITE_TEMPLATES[$sub->site_template] ?? 'Domyślny', ' (') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-ink">
                                    <i class="fa-solid {{ $status[0] }} mr-1 text-muted" aria-hidden="true"></i>{{ $status[1] }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap items-center justify-end gap-2">
                                        <form method="POST" action="{{ route('admin.witryny.przelacz', $sub) }}">
                                            @csrf
                                            <button type="submit" class="{{ $btnPlain }}">Przełącz się<span class="sr-only"> na witrynę {{ $sub->site_name }}</span></button>
                                        </form>
                                        @if ($renameRoute)
                                            <a href="{{ route('admin.witryny.nazwa', $sub) }}" class="{{ $btnPlain }}">
                                                <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Zmień nazwę i adres<span class="sr-only">: {{ $sub->site_name }}</span>
                                            </a>
                                        @endif
                                        <a href="{{ route('admin.witryny.edit', $sub) }}" class="{{ $btnPlain }}">
                                            <i class="fa-solid fa-sliders" aria-hidden="true"></i> Pełna edycja<span class="sr-only">: {{ $sub->site_name }}</span>
                                        </a>
                                        @if (Route::has('admin.witryny.confirm-destroy'))
                                            <a href="{{ route('admin.witryny.confirm-destroy', $sub) }}" class="inline-flex h-11 w-11 items-center justify-center rounded text-muted hover:bg-red-50 hover:text-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark" title="Usuń">
                                                <i class="fa-solid fa-trash" aria-hidden="true"></i><span class="sr-only">Usuń witrynę {{ $sub->site_name }}</span>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{-- Kosz witryn: przywrócenie albo trwałe usunięcie; po SiteSetting::TRASH_DAYS dniach usuwa je witryny:oproznij-kosz. --}}
    @if ($trashed->isNotEmpty())
        <section aria-labelledby="h-site-trash" class="mt-8">
            <h2 id="h-site-trash" class="text-base font-bold text-ink">Kosz</h2>
            <p class="mt-1 text-sm text-muted">Witryny w koszu są niedostępne publicznie, ale ich treść jest zachowana. Po {{ \App\Models\SiteSetting::TRASH_DAYS }} dniach zostaną usunięte trwale.</p>
            <ul class="mt-3 divide-y divide-gray-100 overflow-hidden rounded-md bg-white" role="list">
                @foreach ($trashed as $t)
                    <li class="flex flex-wrap items-center gap-3 px-4 py-3">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-bold text-ink">{{ $t->site_name }}</p>
                            <p class="text-xs text-muted">W koszu od {{ \Illuminate\Support\Carbon::parse($t->trashed_at)->format('d.m.Y H:i') }} · zostanie usunięta trwale {{ $t->purgeAt()->format('d.m.Y') }}</p>
                        </div>
                        <form method="POST" action="{{ route('admin.witryny.restore', $t) }}">
                            @csrf
                            <button type="submit" class="inline-flex min-h-11 items-center gap-2 rounded-md bg-gray-100 px-4 text-sm font-bold text-ink hover:bg-gray-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Przywróć<span class="sr-only"> witrynę {{ $t->site_name }}</span></button>
                        </form>
                        <a href="{{ route('admin.witryny.confirm-destroy', $t) }}" class="inline-flex min-h-11 items-center gap-2 rounded-md px-4 text-sm font-bold text-red-800 hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-700"><i class="fa-solid fa-trash" aria-hidden="true"></i> Usuń trwale teraz<span class="sr-only"> — {{ $t->site_name }}</span></a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
@endsection
