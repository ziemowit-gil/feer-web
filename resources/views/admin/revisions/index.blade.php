@extends('admin.layout')

@section('title', 'Historia zmian — ' . $model->title)

@php
    $fieldLabels = [
        'title' => 'Tytuł',
        'slug' => 'Adres (slug)',
        'excerpt' => 'Zajawka',
        'for_whom' => 'Dla kogo',
        'content' => 'Treść',
        'why' => 'Dlaczego',
        'outcomes' => 'Rezultaty',
        'meta_title' => 'Meta tytuł (SEO)',
        'meta_description' => 'Meta opis (SEO)',
    ];
@endphp

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-ink">Historia zmian</h1>
            <p class="text-sm text-muted">{{ $label }}: <span class="font-bold">{{ $model->title }}</span></p>
        </div>
        <a href="{{ $editUrl }}" class="rounded border border-gray-300 px-4 py-2 text-sm font-bold text-muted hover:text-brand">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Wróć do edycji
        </a>
    </div>

    @if ($revisions->isEmpty())
        <div class="rounded-lg border border-gray-200 bg-white p-8 text-center text-muted">
            Brak zapisanych wersji. Historia zacznie się od następnego zapisu tej treści.
        </div>
    @else
        <p class="mb-4 text-sm text-muted">
            Każdy zapis tworzy wersję. Poniżej wersje od najnowszej; „różnice" pokazują, co zmieniłoby
            przywrócenie danej wersji względem obecnej treści. Przechowujemy do 30 ostatnich wersji.
        </p>

        <ol class="space-y-4">
            @foreach ($revisions as $revision)
                @php
                    $changedFields = collect($fields)->filter(
                        fn ($f) => \App\Support\LineDiff::changed($revision->data[$f] ?? null, $model->{$f})
                    )->values();
                    $isCurrent = $changedFields->isEmpty();
                @endphp
                <li x-data="{ open: false }" class="rounded-lg border border-gray-200 bg-white">
                    <div class="flex flex-wrap items-center justify-between gap-3 p-4">
                        <div class="text-sm">
                            <span class="font-bold text-ink">{{ $revision->created_at?->format('Y-m-d H:i') }}</span>
                            <span class="text-muted">— {{ $revision->user?->name ?? 'system' }}</span>
                            @if ($isCurrent)
                                <span class="ml-2 rounded bg-green-100 px-2 py-0.5 text-xs font-bold text-green-700">wersja bieżąca</span>
                            @else
                                <span class="ml-2 text-xs text-muted">{{ $changedFields->count() }} {{ $changedFields->count() === 1 ? 'zmienione pole' : 'zmienione pola' }}</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            @unless ($isCurrent)
                                <button type="button" @click="open = ! open" :aria-expanded="open.toString()"
                                    class="rounded px-3 py-1.5 text-xs font-bold text-muted hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                                    <i class="fa-solid fa-code-compare" aria-hidden="true"></i>
                                    <span x-text="open ? 'Ukryj różnice' : 'Pokaż różnice'">Pokaż różnice</span>
                                </button>
                                <form method="POST" action="{{ route('admin.historia.restore', ['type' => $type, 'id' => $model->id, 'revision' => $revision->id]) }}"
                                    onsubmit="return confirm('Przywrócić tę wersję? Obecna treść zostanie zapisana w historii.');">
                                    @csrf
                                    <button type="submit" class="rounded bg-brand px-3 py-1.5 text-xs font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                                        <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> Przywróć
                                    </button>
                                </form>
                            @endunless
                        </div>
                    </div>

                    @unless ($isCurrent)
                        <div x-show="open" x-cloak class="border-t border-gray-100 p-4">
                            @foreach ($changedFields as $field)
                                <div class="mb-4 last:mb-0">
                                    <p class="mb-1 text-xs font-bold uppercase tracking-wide text-muted">{{ $fieldLabels[$field] ?? $field }}</p>
                                    <div class="overflow-x-auto rounded border border-gray-200 bg-gray-50 font-mono text-xs leading-relaxed">
                                        @foreach (\App\Support\LineDiff::compare($revision->data[$field] ?? null, $model->{$field}) as $line)
                                            @if ($line['type'] === 'del')
                                                <div class="whitespace-pre-wrap bg-red-50 px-3 text-red-800"><span class="select-none text-red-400">− </span>{{ $line['text'] }}</div>
                                            @elseif ($line['type'] === 'add')
                                                <div class="whitespace-pre-wrap bg-green-50 px-3 text-green-800"><span class="select-none text-green-500">+ </span>{{ $line['text'] }}</div>
                                            @else
                                                <div class="whitespace-pre-wrap px-3 text-gray-500"><span class="select-none text-gray-300">&nbsp;&nbsp;</span>{{ $line['text'] }}</div>
                                            @endif
                                        @endforeach
                                    </div>
                                    <p class="mt-1 text-[11px] text-muted">
                                        <span class="text-red-700">− ta wersja</span> ·
                                        <span class="text-green-700">+ obecna treść</span>
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    @endunless
                </li>
            @endforeach
        </ol>
    @endif
@endsection
