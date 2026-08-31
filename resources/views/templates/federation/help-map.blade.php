@extends('layouts.site')

@section('title', 'Mapa pomocy — ' . $siteSettings->site_name)
@section('meta_description', 'Znajdź punkty pomocy w Krakowie — żywność, schronienie, poradnictwo i inne formy wsparcia.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Mapa pomocy', 'url' => null],
    ]])
@endsection

@section('content')
    <section class="mx-auto max-w-[1400px] px-4 py-12 lg:py-16">
        <p class="mb-3 text-sm font-extrabold uppercase tracking-widest text-brand">Mapa pomocy</p>
        <h1 class="mb-4 text-3xl font-extrabold leading-tight tracking-tight text-ink sm:text-4xl">
            Gdzie szukać wsparcia KraFOS w Małopolsce?
        </h1>
        <p class="mb-8 max-w-2xl text-base leading-relaxed text-muted">
            Zebraliśmy punkty pomocy prowadzone przez organizacje członkowskie {{ $siteSettings->site_name }} i naszych
            partnerów w całej Małopolsce — żywność, schronienie, poradnictwo, wsparcie zdrowotne i prawne.
        </p>

        @php
            $categoriesInUse = $points->pluck('category')->unique();
        @endphp

        <div class="grid gap-6 lg:grid-cols-[320px_1fr] lg:items-start">
            {{-- Lewa kolumna: filtry + lista --}}
            <div class="order-2 lg:order-1">
                @if ($categoriesInUse->isNotEmpty())
                    <fieldset class="mb-5 rounded-lg border border-gray-200 p-4">
                        <legend class="px-1 text-xs font-extrabold uppercase tracking-widest text-muted">Kategorie</legend>
                        <div class="space-y-2">
                            @foreach (\App\Models\HelpPoint::CATEGORIES as $key => $label)
                                @continue (! $categoriesInUse->contains($key))
                                <label class="flex items-center gap-2 text-sm font-medium text-ink">
                                    <input type="checkbox" checked data-help-map-filter="{{ $key }}" class="rounded border-gray-300 text-brand focus:ring-brand">
                                    <i class="fa-solid {{ \App\Models\HelpPoint::CATEGORY_ICONS[$key] ?? 'fa-map-pin' }} text-xs text-muted" aria-hidden="true"></i>
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endif

                <ul class="max-h-[28rem] space-y-2 overflow-y-auto" role="list">
                    @foreach ($points as $point)
                        <li>
                            <button type="button" data-help-map-focus="{{ $point->id }}"
                                class="flex w-full items-start gap-3 rounded-lg border border-gray-200 p-3 text-left transition hover:border-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                                <span class="flex h-8 w-8 flex-none items-center justify-center rounded bg-brand-light text-brand" aria-hidden="true">
                                    <i class="fa-solid {{ $point->categoryIcon() }} text-sm"></i>
                                </span>
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-bold text-ink">{{ $point->name }}</span>
                                    <span class="block truncate text-xs text-muted">{{ $point->address }}</span>
                                </span>
                            </button>
                        </li>
                    @endforeach
                    @if ($points->isEmpty())
                        <li class="text-sm text-muted">Brak punktów pomocy do wyświetlenia.</li>
                    @endif
                </ul>
            </div>

            {{-- Prawa kolumna: mapa --}}
            <div class="order-1 lg:order-2">
                <div id="help-map"
                    class="h-[28rem] w-full rounded-lg lg:h-[36rem]"
                    data-points="{{ $points->map(fn ($p) => [
                        'id' => $p->id, 'name' => $p->name, 'category' => $p->category,
                        'address' => $p->address, 'phone' => $p->phone, 'url' => $p->url,
                        'lat' => (float) $p->lat, 'lng' => (float) $p->lng,
                    ])->toJson() }}"
                    role="img" aria-label="Mapa punktów pomocy w Krakowie">
                </div>
            </div>
        </div>
    </section>
@endsection
