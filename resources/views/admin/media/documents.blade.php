@extends('admin.layout')

@section('title', 'Dostępność dokumentów')

@section('content')
    @php
        $statusText = ['fail' => 'Wymaga poprawy', 'error' => 'Nie udało się sprawdzić', 'warn' => 'Drobne uwagi', 'ok' => 'Bez zastrzeżeń', 'unchecked' => 'Czeka na sprawdzenie'];
        $tab = 'inline-flex min-h-10 items-center gap-2 rounded-md px-4 text-sm font-bold';
    @endphp

    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div class="max-w-3xl">
            <h1 class="text-2xl font-bold text-ink">Dostępność dokumentów</h1>
            <p class="mt-1 text-sm text-ink">
                Pliki PDF i DOCX z biblioteki sprawdzone pod kątem tego, co najczęściej wyklucza osoby korzystające z czytników ekranu:
                tytuł, język, struktura (tagi i nagłówki), warstwa tekstowa i opisy obrazów. To szybkie sprawdzenie, nie pełny audyt
                PDF/UA — poprawki najłatwiej zrobić w programie, w którym powstał dokument, a potem podmienić plik (adres się nie zmieni).
            </p>
        </div>
        <a href="{{ route('admin.multimedia.index') }}" class="{{ $tab }} bg-white text-ink hover:bg-brand-light">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Wróć do plików
        </a>
    </div>

    @if ($remaining > 0)
        <p class="mb-4 rounded-md bg-brand-light px-4 py-3 text-sm text-ink" role="status">
            Sprawdzono część dokumentów. Pozostało: <strong>{{ $remaining }}</strong> —
            <a href="{{ request()->fullUrl() }}" class="font-bold text-brand-dark underline underline-offset-2 hover:no-underline">odśwież stronę</a>, aby sprawdzić kolejne.
        </p>
    @endif

    @if ($total === 0)
        <div class="rounded-md bg-white p-10 text-center">
            <p class="font-bold text-ink">W bibliotece nie ma dokumentów PDF ani DOCX.</p>
        </div>
    @else
        <nav class="mb-4 flex flex-wrap gap-2" aria-label="Filtr wyników">
            <a href="{{ route('admin.multimedia.documents') }}" @if (! $filter) aria-current="page" @endif
                class="{{ $tab }} {{ ! $filter ? 'bg-brand-dark text-white' : 'bg-white text-ink hover:bg-brand-light' }}">Wszystkie ({{ $total }})</a>
            @foreach (['fail', 'warn', 'ok', 'error'] as $s)
                @if ($summary[$s] ?? 0)
                    <a href="{{ route('admin.multimedia.documents', ['status' => $s]) }}" @if ($filter === $s) aria-current="page" @endif
                        class="{{ $tab }} {{ $filter === $s ? 'bg-brand-dark text-white' : 'bg-white text-ink hover:bg-brand-light' }}">{{ $statusText[$s] }} ({{ $summary[$s] }})</a>
                @endif
            @endforeach
        </nav>

        <ul class="space-y-3" role="list">
            @foreach ($rows as $row)
                @php
                    $m = $row['media'];
                    $report = $row['report'];
                    $failed = collect($report['checks'] ?? [])->where('ok', false);
                    $name = $m->getCustomProperty('display_name') ?: $m->file_name;
                @endphp
                <li class="rounded-md bg-white p-4">
                    <div class="flex flex-wrap items-start gap-4">
                        <i class="fa-solid {{ $m->mime_type === 'application/pdf' ? 'fa-file-pdf' : 'fa-file-word' }} mt-1 text-2xl text-brand-dark" aria-hidden="true"></i>
                        <div class="min-w-0 flex-1">
                            <h2 class="break-words text-base font-bold text-ink">
                                <a href="{{ $m->getUrl() }}" target="_blank" rel="noopener" class="hover:underline">{{ $name }}<span class="sr-only"> (otwiera się w nowej karcie)</span></a>
                            </h2>
                            <p class="text-sm text-muted">{{ strtoupper(pathinfo($m->file_name, PATHINFO_EXTENSION)) }} · {{ $m->human_readable_size }} · dodano {{ $m->created_at->format('d.m.Y') }}</p>
                            <p class="mt-1 text-sm font-bold {{ $report['status'] === 'ok' ? 'text-brand-dark' : 'text-ink' }}">
                                <i class="fa-solid {{ $report['status'] === 'ok' ? 'fa-circle-check' : ($report['status'] === 'warn' ? 'fa-circle-exclamation' : 'fa-circle-xmark') }} mr-1" aria-hidden="true"></i>
                                {{ $statusText[$report['status']] ?? $report['status'] }}
                            </p>
                            @if (! empty($report['message']))
                                <p class="text-sm text-ink">{{ $report['message'] }}</p>
                            @endif
                            @if ($failed->isNotEmpty())
                                <ul class="mt-2 space-y-1.5 text-sm" role="list">
                                    @foreach ($failed as $check)
                                        <li>
                                            <span class="font-bold text-ink">{{ $check['label'] }}{{ $check['required'] ? '' : ' (zalecane)' }}:</span>
                                            <span class="text-ink">{{ $check['hint'] }}</span>
                                            @if ($check['value']) <span class="text-muted">({{ $check['value'] }})</span> @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                        <div class="flex flex-none flex-wrap gap-2">
                            <a href="{{ route('admin.multimedia.index', array_filter(['folder' => $m->media_folder_id, 'plik' => $m->id])) }}" class="{{ $tab }} bg-gray-100 text-ink hover:bg-brand-light">
                                Szczegóły i podmiana<span class="sr-only"> pliku {{ $name }}</span>
                            </a>
                            <form method="POST" action="{{ route('admin.multimedia.recheck', $m) }}">
                                @csrf
                                <button type="submit" class="{{ $tab }} bg-gray-100 text-ink hover:bg-brand-light">Sprawdź ponownie<span class="sr-only"> plik {{ $name }}</span></button>
                            </form>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
@endsection
