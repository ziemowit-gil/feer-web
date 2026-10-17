@extends('admin.layout')

@section('title', 'Usuń witrynę')

{{--
    Potwierdzenie usunięcia sub-witryny. Z treścią — wymuszone usunięcie po przepisaniu nazwy (WCAG 3.3.4:
    potwierdzenie nieodwracalnej operacji), z opcjonalną kopią bazy przed usunięciem.
    Kontrast: ink/muted na bieli, czerwony #991b1b na red-50, przycisk biały na red-700; cele ≥ 44 px.
--}}
@section('content')
    @php
        $btn = 'inline-flex min-h-11 items-center gap-2 rounded-lg px-4 text-sm font-bold focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2';
        $total = array_sum($counts);
    @endphp

    <div class="max-w-3xl">
        <a href="{{ route('admin.witryny.index') }}" class="inline-flex min-h-11 items-center gap-2 text-sm font-bold text-brand-dark underline underline-offset-4 hover:no-underline"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Wróć do listy witryn</a>
        <h1 class="mt-2 text-lg font-bold text-ink">{{ $permanent ? 'Usuń trwale' : 'Usuń' }} witrynę „{{ $site->site_name }}”</h1>
        <p class="mt-1 text-sm text-ink">
            @if ($permanent)
                Witryna jest w koszu od {{ \Illuminate\Support\Carbon::parse($site->trashed_at)->format('d.m.Y') }}. Trwałe usunięcie jest <strong>nieodwracalne</strong> — znikną też pliki multimediów.
            @else
                Witryna trafi do kosza: przestanie być dostępna publicznie, ale przez {{ \App\Models\SiteSetting::TRASH_DAYS }} dni będzie można ją przywrócić razem z całą treścią. Potem zostanie usunięta trwale.
            @endif
        </p>

        @if ($errors->any())
            <div role="alert" class="mt-4 rounded-md bg-red-50 p-3 text-sm text-red-800"><i class="fa-solid fa-circle-xmark mr-1.5" aria-hidden="true"></i>@foreach ($errors->all() as $e) {{ $e }} @endforeach</div>
        @endif

        @if ($subsites->isNotEmpty())
            <section aria-labelledby="h-sub" class="mt-4 rounded-md bg-white p-6">
                <h2 id="h-sub" class="font-bold text-ink">Podwitryny tej witryny</h2>
                <p class="mt-1 text-sm text-ink">{{ $permanent ? '„Usuń trwale” usunie także te witryny z całą ich treścią.' : '„Wymuś usunięcie” przeniesie do kosza także te witryny.' }} Możesz też zająć się nimi osobno:</p>
                <ul class="mt-2 list-disc pl-6 text-sm text-ink">
                    @foreach ($subsites as $sub)
                        <li><a href="{{ route('admin.witryny.confirm-destroy', $sub) }}" class="font-bold text-brand-dark underline underline-offset-4 hover:no-underline">{{ $sub->site_name }}</a></li>
                    @endforeach
                </ul>
            </section>
        @endif
            <form method="POST" action="{{ $permanent ? route('admin.witryny.force-destroy', $site) : route('admin.witryny.destroy', $site) }}" class="mt-4 rounded-md bg-white p-6">
                @csrf
                @method('DELETE')

                @if ($counts === [] && $subsites->isEmpty() && ! $permanent)
                    <p class="text-sm text-ink">Witryna nie ma przypisanej treści.</p>
                @else
                    <h2 class="font-bold text-ink">
                        @if ($total === 0)
                            {{ $subsites->isNotEmpty() ? 'Witryna ma podwitryny ('.$subsites->count().'), bez treści' : 'Witryna nie ma treści' }}
                        @else
                            {{ $subsites->isNotEmpty() ? 'Witryna z podwitrynami' : 'Witryna' }} ma treść: {{ $total }} {{ $total === 1 ? 'rekord' : 'rekordów' }}
                        @endif
                    </h2>
                    @if ($permanent)<p class="mt-1 text-sm text-ink">Te rekordy zostaną usunięte trwale. Jeśli treść ma zostać, przywróć witrynę i przenieś ją najpierw do innej.</p>@else<p class="mt-1 text-sm text-ink">Treść zostaje nietknięta w koszu — wróci razem z przywróconą witryną.</p>@endif
                    @if ($counts !== [])
                    <div class="mt-3 max-h-64 overflow-auto rounded-md bg-gray-50" tabindex="0" role="region" aria-label="Treść witryny do usunięcia">
                        <table class="w-full text-left text-sm">
                            <caption class="sr-only">Liczba rekordów witryny w tabelach</caption>
                            <thead class="bg-gray-100 text-xs text-ink"><tr><th scope="col" class="px-3 py-2">Tabela</th><th scope="col" class="px-3 py-2 text-right">Rekordy</th></tr></thead>
                            <tbody>
                                @foreach ($counts as $table => $n)
                                    <tr><th scope="row" class="px-3 py-1.5 font-mono text-xs font-normal text-ink">{{ $table }}</th><td class="px-3 py-1.5 text-right text-ink">{{ $n }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif

                    <div class="mt-4 flex min-h-11 items-center gap-2">
                        <input id="force" name="force" type="checkbox" value="1" required class="h-5 w-5 rounded border-gray-500 text-red-700 focus:ring-red-700">
                        <label for="force" class="text-sm font-bold text-ink">{{ $permanent ? 'Usuń trwale' : 'Wymuś usunięcie' }} — razem z całą treścią{{ $subsites->isNotEmpty() ? ' i podwitrynami ('.$subsites->count().')' : '' }}</label>
                    </div>
@if ($permanent)
                    <div class="flex min-h-11 items-center gap-2">
                        <input id="backup" name="backup" type="checkbox" value="1" checked class="h-5 w-5 rounded border-gray-500 text-brand-dark focus:ring-brand-dark" aria-describedby="backup-help">
                        <label for="backup" class="text-sm font-bold text-ink">Najpierw zrób kopię zapasową bazy</label>
                    </div>
                    <p id="backup-help" class="ml-7 text-xs text-muted">Kopia trafi do System → Kopie zapasowe jako „przed usunięciem witryny” (sama baza, bez plików). Bez udanej kopii nic nie zostanie usunięte.</p>

@endif
                    <label for="confirm-name" class="mt-4 block text-sm font-bold text-ink">Aby potwierdzić, przepisz nazwę witryny: <span class="font-mono">{{ $site->site_name }}</span></label>
                    <input id="confirm-name" name="confirm_name" type="text" required autocomplete="off" value="{{ old('confirm_name') }}"
                           class="mt-1 min-h-11 w-full rounded border-gray-500 text-sm focus:border-red-700 focus:ring-red-700">
                @endif

                <div class="mt-6 flex flex-wrap gap-3">
                    <button type="submit" class="{{ $btn }} bg-red-700 text-white hover:bg-red-800 focus-visible:ring-red-700"><i class="fa-solid fa-trash" aria-hidden="true"></i> {{ $permanent ? 'Usuń trwale' : ($counts === [] && $subsites->isEmpty() ? 'Przenieś do kosza' : 'Wymuś usunięcie') }}</button>
                    <a href="{{ route('admin.witryny.index') }}" class="{{ $btn }} bg-gray-100 text-ink hover:bg-gray-200 focus-visible:ring-brand-dark">Anuluj</a>
                </div>
            </form>
    </div>
@endsection
