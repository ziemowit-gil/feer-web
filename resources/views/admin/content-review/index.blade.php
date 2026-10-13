@extends('admin.layout')

@section('title', 'Przegląd treści')

@section('content')
    @php $btn = 'inline-flex min-h-10 items-center gap-2 rounded-md px-4 text-sm font-bold'; @endphp

    <div class="mb-6 max-w-3xl">
        <h1 class="text-2xl font-bold text-ink">Przegląd treści</h1>
        <p class="mt-1 text-sm text-ink">
            Opublikowane strony, aktualności i działania, których nikt nie zmieniał ani nie sprawdzał od {{ $months }} miesięcy.
            Nieaktualne informacje (godziny, ceny, nazwiska, terminy) mylą odwiedzających. Dla każdej treści: zaktualizuj ją,
            oznacz jako sprawdzoną (jeśli wszystko się zgadza), ustaw datę zdjęcia z publikacji albo oznacz jako archiwalną.
        </p>
    </div>

    <nav class="mb-6 flex flex-wrap items-center gap-2" aria-label="Okres bez zmian">
        <span class="mr-1 text-sm text-ink">Bez zmian od:</span>
        @foreach (\App\Http\Controllers\Admin\ContentReviewController::MONTHS as $m)
            <a href="{{ route('admin.przeglad-tresci.index', ['miesiace' => $m]) }}" @if ($months === $m) aria-current="page" @endif
                class="{{ $btn }} {{ $months === $m ? 'bg-brand-dark text-white' : 'bg-white text-ink hover:bg-brand-light' }}">{{ $m }} miesięcy</a>
        @endforeach
    </nav>

    @if (! $available)
        <p class="rounded-md bg-white p-6 text-sm text-ink">Przegląd zadziała po aktualizacji bazy danych (wdrożenie z migracjami).</p>
    @elseif ($rows->isEmpty())
        <div class="rounded-md bg-white p-10 text-center">
            <i class="fa-solid fa-circle-check mb-2 text-3xl text-brand-dark" aria-hidden="true"></i>
            <p class="font-bold text-ink">Wszystkie opublikowane treści były zmieniane lub sprawdzane w ciągu {{ $months }} miesięcy.</p>
        </div>
    @else
        <form method="POST" action="{{ route('admin.przeglad-tresci.reviewed') }}" x-data="{ selected: [] }">
            @csrf
            <div class="mb-3 flex flex-wrap items-center gap-3">
                <p class="text-sm text-ink">Do przejrzenia: <strong>{{ $rows->count() }}</strong></p>
                <span class="text-sm text-ink" aria-live="polite">Zaznaczono: <strong x-text="selected.length">0</strong></span>
                <button type="submit" :disabled="! selected.length" class="{{ $btn }} bg-brand-dark text-white hover:bg-ink disabled:opacity-60">
                    <i class="fa-solid fa-check" aria-hidden="true"></i> Zaznaczone są aktualne
                </button>
            </div>
            <div class="overflow-x-auto rounded-md bg-white">
                <table class="w-full text-left text-sm">
                    <caption class="sr-only">Treści do przejrzenia</caption>
                    <thead class="bg-gray-50 text-xs font-bold uppercase tracking-widest text-muted">
                        <tr>
                            <th scope="col" class="w-10 px-4 py-3"><span class="sr-only">Zaznacz</span></th>
                            <th scope="col" class="px-4 py-3">Treść</th>
                            <th scope="col" class="px-4 py-3">Ostatnio zmieniona lub sprawdzona</th>
                            <th scope="col" class="px-4 py-3"><span class="sr-only">Akcje</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            @php $value = $row['key'].':'.$row['model']->id; @endphp
                            <tr class="border-t border-gray-100">
                                <td class="px-4 py-3">
                                    <input type="checkbox" name="items[]" value="{{ $value }}" id="rev-{{ $loop->index }}" x-model="selected"
                                        class="h-4 w-4 rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                                    <label for="rev-{{ $loop->index }}" class="sr-only">Zaznacz {{ $row['model']->title }}</label>
                                </td>
                                <th scope="row" class="px-4 py-3 font-normal">
                                    <span class="block font-bold text-ink">{{ $row['model']->title }}</span>
                                    <span class="block text-xs text-muted">{{ $row['type'] }}</span>
                                </th>
                                <td class="whitespace-nowrap px-4 py-3 text-ink">
                                    {{ $row['last']->format('d.m.Y') }}
                                    <span class="block text-xs text-muted">{{ $row['last']->locale('pl')->diffForHumans() }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        @if ($row['edit'])
                                            <a href="{{ $row['edit'] }}" class="{{ $btn }} bg-gray-100 text-ink hover:bg-brand-light">Zaktualizuj<span class="sr-only"> — {{ $row['model']->title }}</span></a>
                                        @endif
                                        <button type="submit" form="rev-one-{{ $loop->index }}" class="{{ $btn }} bg-gray-100 text-ink hover:bg-brand-light">
                                            Sprawdzone, aktualne<span class="sr-only"> — {{ $row['model']->title }}</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </form>
        {{-- Pojedyncze „Sprawdzone, aktualne” — osobne formularze, żeby nie wysyłać zaznaczonych wierszy. --}}
        @foreach ($rows as $row)
            <form id="rev-one-{{ $loop->index }}" method="POST" action="{{ route('admin.przeglad-tresci.reviewed') }}" class="hidden">
                @csrf
                <input type="hidden" name="items[]" value="{{ $row['key'].':'.$row['model']->id }}">
            </form>
        @endforeach
    @endif
@endsection
