@extends('admin.layout')

@section('title', 'Moduły')

@section('content')
    @php
        $count = $cards->count();
        $activeCount = $cards->where('status', 'active')->count();
    @endphp

    <div class="mb-6">
        <h1 class="text-lg font-bold text-ink">Moduły systemu</h1>
        <p class="text-sm text-muted">
            Rozszerzenia CMS-a instalowane z katalogu <code class="font-mono">modules/</code>.
            Aktywne: <strong class="text-ink">{{ $activeCount }}</strong> z {{ $count }}.
        </p>
        <p class="mt-2 text-sm text-muted">
            Wbudowane funkcje strony (aktualności, galeria, FAQ itp.) włączasz i wyłączasz w
            <a href="{{ route('admin.ustawienia.edit', ['tab' => 'modules']) }}" class="font-bold text-brand-dark underline hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">Ustawienia → Moduły</a>.
        </p>
    </div>

    {{-- Komunikaty status/error pokazuje layout panelu. --}}

    @if ($cards->isEmpty())
        <div class="rounded-md bg-gray-50 px-6 py-12 text-center">
            <i class="fa-solid fa-puzzle-piece text-3xl text-muted" aria-hidden="true"></i>
            <p class="mt-3 font-bold text-ink">Brak modułów</p>
            <p class="mt-1 text-sm text-muted">Umieść moduły w katalogu <code class="font-mono">modules/</code> i odśwież stronę.</p>
        </div>
    @else
        <ul class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3" aria-label="Moduły instalowane">
            @foreach ($cards as $card)
                @include('admin.modules.partials.plugin-card', ['card' => $card, 'filterable' => false, 'heading' => 'h2'])
            @endforeach
        </ul>

        @include('admin.modules.partials.plugin-forms', ['cards' => $cards])
    @endif
@endsection
