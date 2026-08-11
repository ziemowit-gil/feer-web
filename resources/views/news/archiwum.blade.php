@extends('layouts.site')

@section('title', 'Archiwum aktualności — ' . $siteSettings->site_name)

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Aktualności', 'url' => route('news.index')],
        ['label' => 'Archiwum', 'url' => null],
    ]])
@endsection

@section('content')
    <section class="mx-auto max-w-5xl px-4 py-12">
        <h1 class="mb-2 text-3xl font-bold text-ink">Archiwum aktualności</h1>
        <p class="mb-10 text-muted">Starsze materiały oraz treści przeniesione z poprzedniej wersji strony.</p>

        {{-- Tabs --}}
        @php
            $tab = request('tab', 'stara-strona');
        @endphp
        <div class="mb-8 flex gap-2 border-b border-gray-200">
            <a href="{{ route('news.archiwum', ['tab' => 'stara-strona']) }}"
                @class([
                    'px-4 py-2 text-sm font-semibold border-b-2 -mb-px transition',
                    'border-brand text-brand'      => $tab === 'stara-strona',
                    'border-transparent text-muted hover:text-ink' => $tab !== 'stara-strona',
                ])
                aria-current="{{ $tab === 'stara-strona' ? 'page' : 'false' }}">
                Ze starej strony
                @if ($legacy->total())
                    <span class="ml-1 rounded-full bg-gray-100 px-1.5 py-0.5 text-xs text-muted">{{ $legacy->total() }}</span>
                @endif
            </a>
            <a href="{{ route('news.archiwum', ['tab' => 'archiwalne']) }}"
                @class([
                    'px-4 py-2 text-sm font-semibold border-b-2 -mb-px transition',
                    'border-brand text-brand'      => $tab === 'archiwalne',
                    'border-transparent text-muted hover:text-ink' => $tab !== 'archiwalne',
                ])
                aria-current="{{ $tab === 'archiwalne' ? 'page' : 'false' }}">
                Archiwalne
                @if ($archived->total())
                    <span class="ml-1 rounded-full bg-gray-100 px-1.5 py-0.5 text-xs text-muted">{{ $archived->total() }}</span>
                @endif
            </a>
        </div>

        @if ($tab === 'archiwalne')
            {{-- ── Treści archiwalne (natywne) ── --}}
            @if ($archived->isEmpty())
                <p class="text-muted">Brak archiwalnych aktualności.</p>
            @else
                <ul class="flex flex-col divide-y divide-gray-100" role="list">
                    @foreach ($archived as $item)
                        @include('news._archive-row', ['item' => $item])
                    @endforeach
                </ul>
                <div class="mt-8">{{ $archived->appends(['tab' => 'archiwalne'])->links() }}</div>
            @endif
        @else
            {{-- ── Ze starej strony ── --}}
            @if ($legacy->isEmpty())
                <p class="text-muted">Brak treści ze starej strony. Uruchom <code>php artisan import:aktualnosci</code>, aby je zaimportować.</p>
            @else
                <ul class="flex flex-col divide-y divide-gray-100" role="list">
                    @foreach ($legacy as $item)
                        @include('news._archive-row', ['item' => $item])
                    @endforeach
                </ul>
                <div class="mt-8">{{ $legacy->appends(['tab' => 'stara-strona'])->links() }}</div>
            @endif
        @endif

        <div class="mt-10 border-t border-gray-100 pt-6">
            <a href="{{ route('news.index') }}" class="text-sm font-bold text-brand hover:text-brand-dark focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                ← Powrót do aktualności
            </a>
        </div>
    </section>
@endsection
