@extends('admin.layout')

@section('title', 'Harmonogram (cron)')

{{--
    System → Harmonogram (cron). Kontrast: ink/muted na bieli i gray-50, czerwony #991b1b na red-50, przyciski biały na brand-dark;
    cele ≥ 44 px; błędy w role="alert", wynik w role="status".
--}}
@section('content')
    @php
        $btn = 'inline-flex min-h-11 items-center gap-2 rounded-lg px-4 text-sm font-bold focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2';
        $primary = $btn.' bg-brand-dark text-white hover:bg-ink';
        $secondary = $btn.' bg-gray-100 text-ink hover:bg-gray-200';
    @endphp

    <div class="mb-6 max-w-3xl">
        <h1 class="text-lg font-bold text-ink">Harmonogram (cron)</h1>
        <p class="text-sm text-muted">Cron co minutę uruchamia harmonogram serwisu: kolejkę zadań (maile, newsletter), synchronizacje z SZO i CRM, publikację i archiwizację treści. Bez niego te rzeczy stoją.</p>
    </div>

    @if (session('status'))
        <div role="status" class="mb-4 max-w-3xl rounded-md bg-brand-light p-3 text-sm text-ink"><i class="fa-solid fa-circle-check mr-1.5" aria-hidden="true"></i>{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div role="alert" class="mb-4 max-w-3xl rounded-md bg-red-50 p-3 text-sm text-red-800"><i class="fa-solid fa-circle-xmark mr-1.5" aria-hidden="true"></i>@foreach ($errors->all() as $e) {{ $e }} @endforeach</div>
    @endif
    @if ($error)
        <p role="alert" class="mb-4 max-w-3xl rounded-md bg-red-50 p-3 text-sm text-red-800">{{ $error }}</p>
    @endif

    @if ($analysis)
        <section aria-labelledby="h-diag" class="mb-6 max-w-3xl rounded-md bg-white p-6">
            <h2 id="h-diag" class="font-bold text-ink">Stan harmonogramu</h2>
            @if ($analysis['ok'])
                <p class="mt-2 text-sm text-ink"><i class="fa-solid fa-circle-check mr-1 text-brand-dark" aria-hidden="true"></i> W porządku — jest dokładnie jeden poprawny wpis.</p>
            @else
                <ul class="mt-2 space-y-1 text-sm text-red-800" role="list">
                    @foreach ($analysis['problems'] as $p)<li><i class="fa-solid fa-triangle-exclamation mr-1" aria-hidden="true"></i>{{ $p }}</li>@endforeach
                </ul>
                <form method="POST" action="{{ route('admin.cron.repair') }}" class="mt-4">
                    @csrf
                    <button type="submit" class="{{ $primary }}"><i class="fa-solid fa-wrench" aria-hidden="true"></i> Napraw harmonogram</button>
                    <p class="mt-2 text-xs text-muted">Zostawia jeden wzorcowy wpis i usuwa wpisy feer-web, które nie działają lub się dublują. Inne wpisy (inne domeny, kopie) zostają bez zmian. To samo w konsoli: <code class="font-mono">scripts/cron.sh</code>.</p>
                </form>
            @endif
            <p class="mt-4 text-xs font-bold text-muted">Wzorcowy wpis</p>
            <code class="mt-1 block overflow-x-auto rounded bg-gray-50 p-2 font-mono text-xs text-ink">{{ $analysis['expected'] }}</code>
        </section>
    @endif

    @if ($content !== null)
        <section aria-labelledby="h-edit" class="mb-6 max-w-3xl rounded-md bg-white p-6">
            <h2 id="h-edit" class="font-bold text-ink">Edycja crontaba</h2>
            <p class="mt-1 text-sm text-muted">Cały crontab konta na serwerze — także wpisy innych serwisów. Przed zapisem sprawdzamy składnię, a poprzednią wersję zachowujemy jako kopię.</p>
            <form method="POST" action="{{ route('admin.cron.update') }}" class="mt-4">
                @csrf
                @method('PUT')
                <label for="cron-content" class="block text-sm font-bold text-ink">Zawartość crontaba</label>
                <textarea id="cron-content" name="content" rows="14" spellcheck="false" aria-describedby="cron-help"
                          class="mt-1 w-full rounded border-gray-500 font-mono text-xs text-ink focus:border-brand-dark focus:ring-brand-dark">{{ old('content', $content) }}</textarea>
                <p id="cron-help" class="mt-1 text-xs text-muted">Format: <code class="font-mono">minuta godzina dzień miesiąc dzień-tygodnia polecenie</code>. Używaj pełnych ścieżek (np. <code class="font-mono">/opt/alt/php84/usr/bin/php</code>) — aliasy jak <code class="font-mono">php84</code> w cronie nie działają.</p>
                <button type="submit" class="{{ $primary }} mt-4" onclick="return confirm('Zapisać crontab na serwerze?')"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Zapisz crontab</button>
            </form>
        </section>
    @endif

    @if ($backups)
        <section aria-labelledby="h-backups" class="max-w-3xl rounded-md bg-white p-6">
            <h2 id="h-backups" class="font-bold text-ink">Kopie crontaba</h2>
            <ul class="mt-3 space-y-2" role="list">
                @foreach ($backups as $b)
                    <li class="flex flex-wrap items-center justify-between gap-2 rounded bg-gray-50 px-3 py-2">
                        <span class="text-sm text-ink">{{ \Illuminate\Support\Carbon::createFromTimestamp($b['time'])->timezone(config('app.timezone'))->format('d.m.Y H:i:s') }} <span class="font-mono text-xs text-muted">{{ $b['name'] }}</span></span>
                        <form method="POST" action="{{ route('admin.cron.restore') }}" onsubmit="return confirm('Przywrócić tę wersję crontaba?')">
                            @csrf
                            <input type="hidden" name="backup" value="{{ $b['name'] }}">
                            <button type="submit" class="{{ $secondary }}"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Przywróć<span class="sr-only"> kopię z {{ \Illuminate\Support\Carbon::createFromTimestamp($b['time'])->format('d.m.Y H:i') }}</span></button>
                        </form>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
@endsection
