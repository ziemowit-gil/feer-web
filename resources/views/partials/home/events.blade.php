@if ($siteSettings->isModuleEnabled('events') && $events->isNotEmpty())
@php
    $sectionAccent = $siteSettings->eventsHomeAccent();
    $nearest = $events->first();
    $link = 'font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
@endphp
{{-- Płasko: karty jako szare pola (wyróżnione — jasnoniebieskie), bez ramek, cieni i plakietek. Kolor sekcji (Ustawienia) zostaje
     na przycisku „Zobacz wszystkie" i pasku daty karty — oba z kolorem przyciemnionym do kontrastu ≥ 4,5:1 z białym tekstem. --}}
<style>
    .he-btn { display: inline-flex; align-items: center; min-height: 2.75rem; padding: .5rem 1.25rem; border-radius: .375rem; background: var(--accent); color: #fff; font-size: .875rem; font-weight: 700; text-decoration: none; }
    .he-btn:hover { background: #1d1d1a; }
    .he-btn:focus-visible { outline: 2px solid #1d1d1a; outline-offset: 2px; }
</style>
<section id="wydarzenia" class="mx-auto max-w-6xl px-4 py-12" style="--accent: {{ $sectionAccent }}">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-ink">Nadchodzące szkolenia i wydarzenia</h2>
            <p class="mt-1 text-muted">Dołącz do naszych szkoleń, warsztatów i spotkań.</p>
        </div>
        <a href="{{ site_route('events.index') }}" class="he-btn">Zobacz wszystkie</a>
    </div>

    {{-- Licznik do najbliższego wydarzenia. Serwer renderuje wartość zapasową
         (działa bez JS), a Alpine uszczegóławia ją i odświeża co minutę. --}}
    <p class="mb-6 inline-flex items-center gap-2 rounded-md bg-brand-light px-4 py-2 text-sm font-medium text-ink"
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
        <i class="fa-solid fa-hourglass-half text-brand-dark" aria-hidden="true"></i>
        <span>Najbliższe wydarzenie <span class="font-bold" x-text="label" aria-live="polite">{{ $nearest->starts_at->locale('pl')->diffForHumans() }}</span></span>
    </p>

    <ul class="grid gap-6 md:grid-cols-3" role="list">
        @foreach ($events as $event)
            @php $cardAccent = $siteSettings->contrastSafeColor($siteSettings->audienceColor($event->audience)); @endphp
            <li @class([
                'flex flex-col overflow-hidden rounded-md',
                'bg-gray-50' => ! $event->is_featured,
                'bg-brand-light' => $event->is_featured,
            ]) style="--accent: {{ $cardAccent }}">
                <div class="flex items-center gap-3 px-5 py-4 text-white" style="background: var(--accent)">
                    <i class="fa-solid {{ $event->typeIcon() }} text-lg" aria-hidden="true"></i>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-bold uppercase tracking-wide text-white">{{ $event->typeLabel() }}@if ($event->is_featured)<span aria-hidden="true"> · </span>Polecane @endif</p>
                        <p class="truncate text-sm font-bold">
                            <time datetime="{{ $event->starts_at->toIso8601String() }}">{{ $event->shortDateLabel() }}</time>
                        </p>
                    </div>
                </div>
                <div class="flex flex-1 flex-col p-5">
                    <h3 class="font-bold text-ink">
                        <a href="{{ site_route('events.show', $event) }}" class="hover:text-brand-dark hover:underline hover:underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">{{ $event->title }}</a>
                    </h3>
                    <p class="mt-1 flex items-center gap-1.5 text-sm text-muted">
                        <i class="fa-solid fa-location-dot text-brand-dark" aria-hidden="true"></i>
                        {{ $event->modeLabel() }}@if ($event->location) · {{ $event->location }}@endif
                    </p>
                    <p class="mt-2 flex-1 text-sm text-ink">{{ \Illuminate\Support\Str::limit($event->lead, 110) }}</p>
                    <a href="{{ site_route('events.show', $event) }}" class="mt-3 inline-flex items-center gap-2 self-start text-sm {{ $link }}">
                        Szczegóły i zapisy<span class="sr-only">: {{ $event->title }}</span> <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
                    </a>
                </div>
            </li>
        @endforeach
    </ul>
</section>
@endif
