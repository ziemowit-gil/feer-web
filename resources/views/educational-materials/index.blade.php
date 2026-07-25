@extends('layouts.site')

@section('title', 'Materiały edukacyjne — ' . $siteSettings->site_name)
@section('meta_description', 'Materiały edukacyjne ' . $siteSettings->site_name . '.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Materiały edukacyjne', 'url' => null],
    ]])
@endsection

@section('content')
    <section class="mx-auto max-w-5xl px-4 py-12">
        <h1 class="{{ $siteSettings->materials_intro ? 'mb-4' : 'mb-8' }} text-3xl font-bold text-ink">Materiały edukacyjne</h1>

        @if ($siteSettings->materials_intro)
            <div class="prose mb-8 max-w-2xl text-muted">{!! $siteSettings->materials_intro !!}</div>
        @endif

        @if ($siteSettings->materials_notice)
            <div class="mb-8 flex gap-3 rounded-lg border border-brand/20 bg-brand-light p-4">
                <i class="fa-solid fa-circle-info mt-0.5 flex-none text-lg text-brand" aria-hidden="true"></i>
                <div class="prose prose-sm max-w-none text-ink">{!! $siteSettings->materials_notice !!}</div>
            </div>
        @endif

        @if ($materials->isEmpty())
            <p class="text-muted">Brak materiałów do wyświetlenia.</p>
        @else
            @php
                // Grupowanie wg grupy docelowej; materiały bez grupy trafiają na koniec.
                $groups = $materials->groupBy(fn ($material) => $material->target_group ?: '');
                $hasNamedGroups = $groups->keys()->filter(fn ($key) => $key !== '')->isNotEmpty();
            @endphp

            @foreach ($groups as $groupName => $groupMaterials)
                <div class="mb-10">
                    @if ($groupName !== '')
                        <h2 class="mb-4 flex items-center gap-2 text-xl font-bold text-ink">
                            <i class="fa-solid fa-user-group text-brand" aria-hidden="true"></i> {{ $groupName }}
                        </h2>
                    @elseif ($hasNamedGroups)
                        <h2 class="mb-4 text-xl font-bold text-ink">Pozostałe materiały</h2>
                    @endif

                    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($groupMaterials as $material)
                            @include('partials.material-card', ['material' => $material])
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endif

        {{-- Zapis na powiadomienia o nowych materiałach --}}
        <div id="zapis" class="mt-4 rounded-xl border border-gray-200 bg-gray-50 p-6 sm:p-8">
            <div class="mx-auto max-w-xl text-center">
                <span class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-brand-light text-xl text-brand" aria-hidden="true">
                    <i class="fa-solid fa-bell"></i>
                </span>
                <h2 class="text-xl font-bold text-ink">Powiadomienia o nowych materiałach</h2>
                <p class="mt-1 text-sm text-muted">Zostaw swój adres e-mail, a damy Ci znać, gdy pojawią się nowe materiały.</p>

                @if (session('materials_subscribed'))
                    <p class="mx-auto mt-4 flex w-fit items-center gap-2 rounded-lg bg-green-50 px-4 py-2 text-sm font-bold text-green-700">
                        <i class="fa-solid fa-circle-check" aria-hidden="true"></i> Dziękujemy! Adres został zapisany.
                    </p>
                @else
                    <form method="POST" action="{{ route('materials.subscribe') }}" class="mt-4">
                        @csrf
                        <div class="mx-auto flex max-w-md flex-col gap-2 sm:flex-row">
                            <label for="subscribe-email" class="sr-only">Adres e-mail</label>
                            <input type="email" id="subscribe-email" name="email" value="{{ old('email') }}" required
                                placeholder="twoj@email.pl" autocomplete="email"
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                            <button type="submit" class="flex-none rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark">
                                Zapisz się
                            </button>
                        </div>
                        @error('email') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                        <p class="mt-2 text-xs text-muted">Wykorzystamy Twój adres wyłącznie do informowania o nowych materiałach.</p>
                    </form>
                @endif
            </div>
        </div>
    </section>
@endsection
