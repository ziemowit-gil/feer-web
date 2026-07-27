@extends('layouts.site')

@section('title', 'Nadchodzące szkolenia i wydarzenia — ' . $siteSettings->site_name)
@section('meta_description', 'Nadchodzące szkolenia, warsztaty i wydarzenia organizowane przez ' . $siteSettings->site_name . '.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Szkolenia i wydarzenia', 'url' => null],
    ]])
@endsection

@section('content')
    <section class="mx-auto max-w-5xl px-4 py-12">
        <h1 class="mb-2 text-3xl font-bold text-ink">Nadchodzące szkolenia i wydarzenia</h1>
        <p class="mb-6 max-w-2xl text-muted">Sprawdź, co przygotowaliśmy. Zapisy prowadzimy do wyczerpania miejsc — kliknij wydarzenie, aby poznać szczegóły i się zapisać.</p>

        @if ($events->isNotEmpty())
            @php $nearest = $events->first(); @endphp
            <p class="mb-8 inline-flex items-center gap-2 rounded-lg bg-brand-light px-4 py-2 text-sm font-medium text-brand-dark"
                x-data="{
                    iso: '{{ $nearest->starts_at->toIso8601String() }}',
                    label: @js($nearest->starts_at->locale('pl')->diffForHumans()),
                    upd() {
                        const diff = new Date(this.iso) - new Date();
                        if (diff <= 0) { this.label = 'już wkrótce'; return; }
                        const d = Math.floor(diff / 86400000),
                              h = Math.floor(diff % 86400000 / 3600000),
                              m = Math.floor(diff % 3600000 / 60000);
                        this.label = d > 0 ? `za ${d} ${d === 1 ? 'dzień' : 'dni'} ${h} godz` : (h > 0 ? `za ${h} godz ${m} min` : `za ${m} min`);
                    }
                }"
                x-init="upd(); setInterval(() => upd(), 60000)">
                <i class="fa-solid fa-hourglass-half" aria-hidden="true"></i>
                <span>Najbliższe: <span class="font-bold" x-text="label" aria-live="polite">{{ $nearest->starts_at->locale('pl')->diffForHumans() }}</span> — „{{ \Illuminate\Support\Str::limit($nearest->title, 50) }}"</span>
            </p>
        @endif

        @if ($events->isEmpty())
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-8 text-center text-muted">
                Obecnie nie mamy zaplanowanych wydarzeń. Zajrzyj wkrótce albo napisz do nas przez
                <a href="{{ route('contact.show') }}" class="font-bold text-brand hover:text-brand-dark">formularz kontaktowy</a>.
            </div>
        @else
            <ul class="grid gap-5 sm:grid-cols-2">
                @foreach ($events as $event)
                    @php $accent = $siteSettings->contrastSafeColor($siteSettings->audienceColor($event->audience)); @endphp
                    <li @class([
                            'flex flex-col overflow-hidden rounded-xl bg-white shadow-sm transition hover:shadow-md',
                            'border border-gray-200' => ! $event->is_featured,
                            'border-2 border-amber-400 ring-2 ring-amber-200' => $event->is_featured,
                        ]) style="--accent: {{ $accent }}">
                        <div class="h-1.5 w-full" style="background: var(--accent)" aria-hidden="true"></div>
                        <div class="flex flex-1 flex-col p-6">
                            <div class="mb-3 flex flex-wrap items-center gap-2 text-xs">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 font-bold text-white" style="background: var(--accent)">
                                    <i class="fa-solid {{ $event->typeIcon() }}" aria-hidden="true"></i>
                                    {{ $event->typeLabel() }}
                                </span>
                                @if ($event->is_featured)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-400 px-2.5 py-1 font-bold text-amber-950">
                                        <i class="fa-solid fa-star" aria-hidden="true"></i> Polecane
                                    </span>
                                @endif
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-gray-700">
                                    <i class="fa-solid fa-location-dot" aria-hidden="true" style="color: var(--accent)"></i>
                                    {{ $event->modeLabel() }}
                                </span>
                            </div>

                            <p class="flex items-center gap-1.5 text-sm font-bold text-ink">
                                <i class="fa-solid fa-calendar-day" aria-hidden="true" style="color: var(--accent)"></i>
                                <time datetime="{{ $event->starts_at->toIso8601String() }}">{{ $event->shortDateLabel() }}</time>
                            </p>

                            <h2 class="mt-2 text-xl font-bold text-ink">
                                <a href="{{ route('events.show', $event) }}" class="hover:underline" style="text-decoration-color: var(--accent)">{{ $event->title }}</a>
                            </h2>
                            <p class="mt-2 flex-1 text-muted">{{ $event->lead }}</p>

                            <a href="{{ route('events.show', $event) }}" class="mt-4 inline-flex items-center gap-2 self-start font-bold" style="color: var(--accent)">
                                Zobacz szczegóły <i class="fa-solid fa-arrow-right text-sm" aria-hidden="true"></i>
                            </a>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
@endsection
