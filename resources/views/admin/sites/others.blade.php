@extends('admin.layout')

@section('title', 'Usuń pozostałe witryny')

{{--
    „Usuń pozostałe witryny” (MainSite::removeOthers): wszystkie witryny poza główną do kosza albo trwale. Potwierdzenie
    zaznaczeniem i przepisaniem nazwy witryny, która zostaje (WCAG 3.3.4). Kontrast: ink/muted na bieli, przycisk biały
    na red-700; cele ≥ 44 px; płasko, bez ramek.
--}}
@section('content')
    @php
        $btn = 'inline-flex min-h-11 items-center gap-2 rounded px-4 text-sm font-bold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2';
        $activeSites = array_sum(array_map(fn ($r) => 1 + $r['subsites'], $active));
        $trashedSites = array_sum(array_map(fn ($r) => 1 + $r['subsites'], $trashed));
        $records = array_sum(array_column($active, 'records'));
        $trashedRecords = array_sum(array_column($trashed, 'records'));
        $mode = old('mode', $active === [] ? 'permanent' : 'trash');
        $pl = fn (int $n, string $one, string $few, string $many) => $n.' '.($n === 1 ? $one : (in_array($n % 10, [2, 3, 4], true) && ! in_array($n % 100, [12, 13, 14], true) ? $few : $many));
    @endphp

    <div class="max-w-3xl">
        <p class="mb-4 text-sm">
            <a href="{{ route('admin.witryny.index') }}" class="inline-flex min-h-11 items-center gap-2 font-bold text-brand-dark underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Zarządzaj subwitrynami
            </a>
        </p>

        <h1 class="text-lg font-bold text-ink">Zostaw tylko witrynę „{{ $main->site_name }}”</h1>
        <p class="mt-1 text-sm text-ink">Witryna główna „{{ $main->site_name }}” zostaje bez zmian. Usuniesz wszystkie pozostałe witryny razem z ich treścią.</p>

        @if ($errors->any())
            <div role="alert" class="mt-4 rounded-md bg-red-50 p-3 text-sm text-red-800"><i class="fa-solid fa-circle-xmark mr-1.5" aria-hidden="true"></i>@foreach ($errors->all() as $e) {{ $e }} @endforeach</div>
        @endif

        @foreach (['active' => [$active, 'Witryny do usunięcia', 'Witryny do usunięcia: nazwa, liczba podwitryn i rekordów'], 'trashed' => [$trashed, 'Witryny już w koszu', 'Witryny w koszu: nazwa, liczba podwitryn i rekordów']] as $key => [$list, $heading, $caption])
            @if ($list !== [])
                <section aria-labelledby="h-others-{{ $key }}" class="mt-4 rounded-md bg-white p-6">
                    <h2 id="h-others-{{ $key }}" class="font-bold text-ink">{{ $heading }}</h2>
                    @if ($key === 'trashed')
                        <p class="mt-1 text-sm text-ink">Znikną tylko przy trwałym usunięciu. Przy przeniesieniu do kosza zostają tam, gdzie są.</p>
                    @endif
                    <div class="mt-3 max-h-72 overflow-auto rounded-md bg-gray-50" tabindex="0" role="region" aria-label="{{ $heading }}">
                        <table class="w-full text-left text-sm">
                            <caption class="sr-only">{{ $caption }}</caption>
                            <thead class="bg-gray-100 text-ink">
                                <tr>
                                    <th scope="col" class="px-3 py-2 font-bold">Witryna</th>
                                    <th scope="col" class="px-3 py-2 text-right font-bold">Podwitryny</th>
                                    <th scope="col" class="px-3 py-2 text-right font-bold">Rekordy</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($list as $r)
                                    <tr>
                                        <th scope="row" class="px-3 py-2 font-normal text-ink">{{ $r['site']->site_name }}@if ($r['site']->isBipSite()) <span class="text-xs font-bold text-muted">· BIP</span>@endif</th>
                                        <td class="px-3 py-2 text-right text-ink">{{ $r['subsites'] }}</td>
                                        <td class="px-3 py-2 text-right text-ink">{{ $r['records'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif
        @endforeach

        <form method="POST" action="{{ route('admin.witryny.pozostale.destroy') }}" class="mt-4 rounded-md bg-white p-6">
            @csrf
            @method('DELETE')

            <fieldset>
                <legend class="font-bold text-ink">Jak usunąć</legend>
                <div class="mt-2 space-y-2">
                    <div class="flex min-h-11 items-start gap-2">
                        <input id="mode-trash" name="mode" type="radio" value="trash" @checked($mode === 'trash') @disabled($active === []) class="mt-0.5 h-5 w-5 border-gray-500 text-brand-dark focus:ring-brand-dark" aria-describedby="mode-trash-help">
                        <div>
                            <label for="mode-trash" class="text-sm font-bold text-ink">Do kosza ({{ $pl($activeSites, 'witryna', 'witryny', 'witryn') }}, {{ $pl($records, 'rekord', 'rekordy', 'rekordów') }})</label>
                            <p id="mode-trash-help" class="text-sm text-ink">Witryny znikną publicznie, a przez {{ \App\Models\SiteSetting::TRASH_DAYS }} dni da się je przywrócić z treścią. Potem zostaną usunięte trwale.</p>
                        </div>
                    </div>
                    <div class="flex min-h-11 items-start gap-2">
                        <input id="mode-permanent" name="mode" type="radio" value="permanent" @checked($mode === 'permanent') class="mt-0.5 h-5 w-5 border-gray-500 text-red-700 focus:ring-red-700" aria-describedby="mode-permanent-help">
                        <div>
                            <label for="mode-permanent" class="text-sm font-bold text-ink">Trwale ({{ $pl($activeSites + $trashedSites, 'witryna', 'witryny', 'witryn') }}, {{ $pl($records + $trashedRecords, 'rekord', 'rekordy', 'rekordów') }})</label>
                            <p id="mode-permanent-help" class="text-sm text-ink">Nieodwracalne: znikną witryny, ich treść i pliki multimediów, także witryny, które już są w koszu.</p>
                        </div>
                    </div>
                </div>
            </fieldset>

            <div class="mt-4 flex min-h-11 items-center gap-2">
                <input id="backup" name="backup" type="checkbox" value="1" @checked(old('backup', '1')) class="h-5 w-5 rounded border-gray-500 text-brand-dark focus:ring-brand-dark" aria-describedby="backup-help">
                <label for="backup" class="text-sm font-bold text-ink">Przy trwałym usunięciu najpierw zrób kopię zapasową bazy</label>
            </div>
            <p id="backup-help" class="ml-7 text-sm text-muted">Kopia trafi do System → Kopie zapasowe (sama baza, bez plików). Bez udanej kopii nic nie zostanie usunięte.</p>

            <div class="mt-4 flex min-h-11 items-center gap-2">
                <input id="force" name="force" type="checkbox" value="1" required class="h-5 w-5 rounded border-gray-500 text-red-700 focus:ring-red-700">
                <label for="force" class="text-sm font-bold text-ink">Usuń wszystkie witryny poza „{{ $main->site_name }}”</label>
            </div>

            <label for="confirm-name" class="mt-4 block text-sm font-bold text-ink">Aby potwierdzić, przepisz nazwę witryny, która zostaje: <span class="font-mono">{{ $main->site_name }}</span></label>
            <input id="confirm-name" name="confirm_name" type="text" required autocomplete="off" value="{{ old('confirm_name') }}"
                   class="mt-1 min-h-11 w-full rounded border-gray-500 text-sm focus:border-red-700 focus:ring-red-700">

            <div class="mt-6 flex flex-wrap gap-3">
                <button type="submit" class="{{ $btn }} bg-red-700 text-white hover:bg-red-800 focus-visible:ring-red-700"><i class="fa-solid fa-trash" aria-hidden="true"></i> Usuń pozostałe witryny</button>
                <a href="{{ route('admin.witryny.index') }}" class="{{ $btn }} bg-gray-100 text-ink hover:bg-gray-200 focus-visible:ring-brand-dark">Anuluj</a>
            </div>
        </form>
    </div>
@endsection
