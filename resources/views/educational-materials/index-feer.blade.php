@extends('layouts.site')

@section('title', 'Materiały edukacyjne — ' . $siteSettings->site_name)
@section('meta_description', 'Materiały edukacyjne ' . $siteSettings->site_name . '.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Materiały edukacyjne', 'url' => null],
    ]])
@endsection

{{--
    Materiały edukacyjne w układzie FEER: jasny nagłówek, po lewej nawigacja po grupach docelowych, po prawej materiały
    jako atrakcyjne karty z dużym podglądem (miniatura nagrania / pierwsza strona PDF) — bez ramek. Treści płatne zachowują logikę dostępu
    (partials/material-card-feer): zablokowane pokazują zachętę do zakupu lub logowania. Kontrast: ink/muted na bieli, linki brand-dark.
--}}
@section('content')
    @php
        $groups = $materials->groupBy(fn ($m) => $m->target_group ?: '');
        $hasNamedGroups = $groups->keys()->filter(fn ($k) => $k !== '')->isNotEmpty();
        $groupLabel = fn ($key) => $key === ''
            ? ($hasNamedGroups ? 'Pozostałe materiały' : 'Wszystkie materiały')
            : (\App\Models\EducationalMaterial::TARGET_GROUPS[$key] ?? $key);
        $actionPrimary = 'inline-flex min-h-11 items-center gap-2 rounded-md bg-brand px-4 text-sm font-bold text-white transition hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2';
        $actionGhost = 'inline-flex min-h-11 items-center gap-2 rounded-md bg-gray-100 px-4 text-sm font-bold text-ink transition hover:bg-gray-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2';
    @endphp

    <section class="bg-gray-50">
        <div class="mx-auto max-w-6xl px-4 py-12 md:py-16">
            <h1 class="text-3xl font-extrabold leading-tight tracking-tight text-ink md:text-5xl">Materiały edukacyjne</h1>
            @if ($siteSettings->materials_intro)
                <div class="prose mt-4 max-w-2xl text-lg text-ink">{!! $siteSettings->materials_intro !!}</div>
            @endif
        </div>
    </section>

    <div class="mx-auto max-w-6xl px-4 py-12">
        @if ($siteSettings->materials_notice)
            <div class="mb-10 max-w-3xl rounded-md bg-gray-50 p-5 text-sm leading-relaxed text-ink">
                <div class="prose prose-sm max-w-none text-ink">{!! $siteSettings->materials_notice !!}</div>
            </div>
        @endif

        @if ($materials->isEmpty())
            <p class="text-muted">Brak materiałów do wyświetlenia.</p>
        @else
            <div class="grid gap-10 lg:grid-cols-[14rem_minmax(0,1fr)] lg:gap-14">
                @if ($groups->count() > 1)
                    <nav aria-label="Grupy materiałów" class="lg:sticky lg:top-6 lg:self-start">
                        <p class="mb-3 text-xs font-bold uppercase tracking-widest text-muted">Dla kogo</p>
                        <ul class="flex gap-2 overflow-x-auto pb-2 lg:flex-col lg:gap-1 lg:overflow-visible lg:pb-0" role="list">
                            @foreach ($groups as $gKey => $items)
                                <li class="shrink-0">
                                    <a href="#grupa-{{ $loop->index }}"
                                       class="flex min-h-11 items-center justify-between gap-3 rounded-md bg-gray-50 px-4 py-2 text-sm font-bold text-ink transition hover:bg-gray-100 hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand lg:bg-transparent lg:px-3">
                                        <span>{{ $groupLabel($gKey) }}</span>
                                        <span class="text-muted">{{ $items->count() }}</span>
                                    </a>
                                </li>
                            @endforeach
                            <li class="shrink-0"><a href="#zapis" class="flex min-h-11 items-center rounded-md px-4 py-2 text-sm font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand lg:px-3">Powiadomienia →</a></li>
                        </ul>
                    </nav>
                @endif

                <div class="min-w-0 space-y-14 {{ $groups->count() > 1 ? '' : 'lg:col-span-2' }}">
                    @foreach ($groups as $gKey => $items)
                        <section id="grupa-{{ $loop->index }}" class="scroll-mt-24" aria-labelledby="grupa-h-{{ $loop->index }}">
                            <h2 id="grupa-h-{{ $loop->index }}" class="mb-4 text-2xl font-bold text-ink">{{ $groupLabel($gKey) }}</h2>

                            <ul class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3" role="list">
                                @foreach ($items as $material)
                                    <li>@include('partials.material-card-feer', ['material' => $material])</li>
                                @endforeach
                            </ul>
                        </section>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Zapis na powiadomienia o nowych materiałach --}}
        <section id="zapis" class="mt-16 scroll-mt-24 rounded-md bg-gray-50 p-6 sm:p-8" aria-labelledby="zapis-h">
            <div class="max-w-xl">
                <h2 id="zapis-h" class="text-2xl font-bold text-ink">Powiadomienia o nowych materiałach</h2>
                <p class="mt-1 text-sm text-ink">Zostaw swój adres e-mail, a damy Ci znać, gdy pojawią się nowe materiały.</p>

                @if (session('materials_subscribed'))
                    <p class="mt-4 text-sm font-bold text-green-800" role="status">Dziękujemy! Adres został zapisany.</p>
                @else
                    <form method="POST" action="{{ route('materials.subscribe') }}" class="mt-4">
                        @csrf
                        <div class="flex flex-col gap-2 sm:flex-row">
                            <label for="subscribe-email" class="sr-only">Adres e-mail</label>
                            <input type="email" id="subscribe-email" name="email" value="{{ old('email') }}" required placeholder="twoj@email.pl" autocomplete="email"
                                   class="min-h-11 w-full rounded-md border-gray-500 focus:border-brand focus:ring-brand">
                            <button type="submit" class="{{ $actionPrimary }} justify-center">Zapisz się</button>
                        </div>
                        @error('email') <p class="mt-2 text-sm text-red-700" role="alert">{{ $message }}</p> @enderror
                        <p class="mt-2 text-xs text-muted">Wykorzystamy Twój adres wyłącznie do informowania o nowych materiałach.</p>
                    </form>
                @endif
            </div>
        </section>
    </div>
@endsection
