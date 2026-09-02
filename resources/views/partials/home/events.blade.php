@if ($siteSettings->isModuleEnabled('events') && $events->isNotEmpty())
@php
    $sectionAccent = $siteSettings->eventsHomeAccent();
    $nearest = $events->first();
@endphp
<section id="wydarzenia" class="mx-auto max-w-6xl px-4 py-12" style="--accent: {{ $sectionAccent }}">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-ink">Nadchodzące szkolenia i wydarzenia</h2>
            <p class="mt-1 text-muted">Dołącz do naszych szkoleń, warsztatów i spotkań.</p>
        </div>
        <a href="{{ site_route('events.index') }}" class="rounded px-4 py-2 text-xs font-bold uppercase tracking-wide text-white transition hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2" style="background: var(--accent); outline-color: var(--accent)">Zobacz wszystkie</a>
    </div>

    {{-- Licznik do najbliższego wydarzenia. Serwer renderuje wartość zapasową
         (działa bez JS), a Alpine uszczegóławia ją i odświeża co minutę. --}}
    <p class="mb-6 inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium"
        style="background: color-mix(in srgb, var(--accent) 12%, white); color: color-mix(in srgb, var(--accent) 75%, black)"
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
        <i class="fa-solid fa-hourglass-half" aria-hidden="true" style="color: var(--accent)"></i>
        <span>Najbliższe wydarzenie <span class="font-bold" x-text="label" aria-live="polite">{{ $nearest->starts_at->locale('pl')->diffForHumans() }}</span></span>
    </p>

    <ul class="grid gap-6 md:grid-cols-3">
        @foreach ($events as $event)
            @php $cardAccent = $siteSettings->contrastSafeColor($siteSettings->audienceColor($event->audience)); @endphp
            <li @class([
                'flex flex-col overflow-hidden rounded-xl bg-white shadow-sm transition hover:shadow-md',
                'border border-gray-200' => ! $event->is_featured,
                'border-2 border-amber-400 ring-2 ring-amber-200' => $event->is_featured,
            ]) style="--accent: {{ $cardAccent }}">
                <div class="flex items-center gap-3 px-5 py-4 text-white" style="background: var(--accent)">
                    <i class="fa-solid {{ $event->typeIcon() }} text-lg" aria-hidden="true"></i>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-bold uppercase tracking-wide text-white/80">{{ $event->typeLabel() }}</p>
                        <p class="truncate text-sm font-bold">
                            <time datetime="{{ $event->starts_at->toIso8601String() }}">{{ $event->shortDateLabel() }}</time>
                        </p>
                    </div>
                    @if ($event->is_featured)
                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-400 px-2 py-0.5 text-xs font-bold text-amber-950" title="Wydarzenie wyróżnione">
                            <i class="fa-solid fa-star" aria-hidden="true"></i> Polecane
                        </span>
                    @endif
                </div>
                <div class="flex flex-1 flex-col p-5">
                    <h3 class="font-bold text-ink">
                        <a href="{{ site_route('events.show', $event) }}" class="hover:text-brand focus-visible:outline-2 focus-visible:outline-offset-2" style="outline-color: var(--accent)">{{ $event->title }}</a>
                    </h3>
                    <p class="mt-1 flex items-center gap-1.5 text-xs text-muted">
                        <i class="fa-solid fa-location-dot" aria-hidden="true" style="color: var(--accent)"></i>
                        {{ $event->modeLabel() }}@if ($event->location) · {{ $event->location }}@endif
                    </p>
                    <p class="mt-2 flex-1 text-sm text-muted">{{ \Illuminate\Support\Str::limit($event->lead, 110) }}</p>
                    <a href="{{ site_route('events.show', $event) }}" class="mt-3 inline-flex items-center gap-2 self-start text-sm font-bold" style="color: var(--accent)">
                        Szczegóły i zapisy <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
                    </a>
                </div>
            </li>
        @endforeach
    </ul>
</section>
@endif
