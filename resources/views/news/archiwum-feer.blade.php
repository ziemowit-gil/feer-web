@extends('layouts.site')

@section('title', 'Archiwum aktualności — ' . $siteSettings->site_name)

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Aktualności', 'url' => route('news.index')],
        ['label' => 'Archiwum', 'url' => null],
    ]])
@endsection

{{-- Archiwum aktualności w stylu FEER: jasny nagłówek, przyciski kart (aktywny ciemny, białe pismo 16,9:1), płaskie wiersze. --}}
@section('content')
    @php
        $tab = request('tab', 'stara-strona');
        $pill = 'inline-flex min-h-11 items-center gap-2 rounded-md px-4 text-sm font-bold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2';
    @endphp

    <section class="bg-gray-50">
        <div class="mx-auto max-w-6xl px-4 py-12 md:py-16">
            <h1 class="text-3xl font-extrabold leading-tight tracking-tight text-ink md:text-5xl">Archiwum aktualności</h1>
            <p class="mt-4 max-w-2xl text-lg text-ink">Starsze materiały oraz treści przeniesione z poprzedniej wersji strony.</p>
        </div>
    </section>

    <div class="mx-auto max-w-6xl px-4 py-10">
        <nav aria-label="Zakres archiwum" class="mb-8">
            <ul class="flex flex-wrap gap-2" role="list">
                @foreach ([['stara-strona', 'Ze starej strony', $legacy->total()], ['archiwalne', 'Archiwalne', $archived->total()]] as [$key, $label, $count])
                    <li>
                        <a href="{{ route('news.archiwum', ['tab' => $key]) }}" @if ($tab === $key || ($key === 'stara-strona' && $tab !== 'archiwalne')) aria-current="page" @endif
                           class="{{ $pill }} {{ ($tab === $key || ($key === 'stara-strona' && $tab !== 'archiwalne')) ? 'bg-ink text-white' : 'bg-gray-100 text-ink hover:bg-gray-200' }}">
                            {{ $label }}@if ($count)<span class="font-medium opacity-100">({{ $count }})</span>@endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        @if ($tab === 'archiwalne')
            @if ($archived->isEmpty())
                <p class="text-muted">Brak archiwalnych aktualności.</p>
            @else
                <ul class="space-y-3" role="list">
                    @foreach ($archived as $item) @include('news._archive-row-feer', ['item' => $item]) @endforeach
                </ul>
                <div class="mt-8">{{ $archived->appends(['tab' => 'archiwalne'])->links() }}</div>
            @endif
        @else
            @if ($legacy->isEmpty())
                <p class="text-muted">Brak treści ze starej strony.</p>
            @else
                <ul class="space-y-3" role="list">
                    @foreach ($legacy as $item) @include('news._archive-row-feer', ['item' => $item]) @endforeach
                </ul>
                <div class="mt-8">{{ $legacy->appends(['tab' => 'stara-strona'])->links() }}</div>
            @endif
        @endif

        <p class="mt-10"><a href="{{ route('news.index') }}" class="text-sm font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">← Powrót do aktualności</a></p>
    </div>
@endsection
