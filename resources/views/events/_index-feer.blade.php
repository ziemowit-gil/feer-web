{{--
    Lista wydarzeń w układzie FEER: spokojny tytuł z akcentem, „najbliższe” jako płaski pasek, wydarzenia jako wiersze z blokiem daty
    (dzień + miesiąc) po lewej, typem, trybem, tytułem i zajawką. Polecane mają pomarańczowy znacznik. Kontrast: ink/muted na bieli.
--}}
<section class="mx-auto max-w-5xl px-4 py-10">
    <h1 class="text-2xl font-bold leading-tight text-ink md:text-3xl">Nadchodzące szkolenia i wydarzenia</h1>
    <span class="mt-3 block h-1 w-14 bg-brand" aria-hidden="true"></span>
    <p class="mt-5 max-w-2xl text-lg text-ink">Sprawdź, co przygotowaliśmy. Zapisy prowadzimy do wyczerpania miejsc — kliknij wydarzenie, aby poznać szczegóły i się zapisać.</p>

    @if ($events->isNotEmpty())
        @php $nearest = $events->first(); @endphp
        <p class="mt-6 inline-flex items-center gap-2 rounded-md bg-brand-light px-4 py-2 text-sm font-bold text-ink"
            x-data="{
                iso: '{{ $nearest->starts_at->toIso8601String() }}',
                label: @js($nearest->starts_at->locale('pl')->diffForHumans()),
                upd() {
                    const diff = new Date(this.iso) - new Date();
                    if (diff <= 0) { this.label = 'już wkrótce'; return; }
                    const d = Math.floor(diff / 86400000), h = Math.floor(diff % 86400000 / 3600000), m = Math.floor(diff % 3600000 / 60000);
                    this.label = d > 0 ? `za ${d} ${d === 1 ? 'dzień' : 'dni'} ${h} godz` : (h > 0 ? `za ${h} godz ${m} min` : `za ${m} min`);
                }
            }"
            x-init="upd(); setInterval(() => upd(), 60000)">
            <i class="fa-solid fa-hourglass-half text-brand-dark" aria-hidden="true"></i>
            <span>Najbliższe: <span x-text="label" aria-live="polite">{{ $nearest->starts_at->locale('pl')->diffForHumans() }}</span> — „{{ \Illuminate\Support\Str::limit($nearest->title, 50) }}”</span>
        </p>
    @endif

    @if ($events->isEmpty())
        <div class="mt-10 rounded-md bg-gray-50 p-8 text-center text-muted">
            Obecnie nie mamy zaplanowanych wydarzeń. Zajrzyj wkrótce albo napisz do nas przez
            <a href="{{ route('contact.show') }}" class="font-bold text-brand-dark underline underline-offset-4 hover:text-ink">formularz kontaktowy</a>.
        </div>
    @else
        <ul class="mt-10 space-y-3" role="list">
            @foreach ($events as $event)
                @php $accent = $event->is_featured ? '#ea8f00' : 'var(--color-brand)'; @endphp
                <li>
                    <a href="{{ site_route('events.show', $event) }}" class="feer-card group flex items-stretch gap-5 rounded-md bg-white py-4 pl-4 pr-3 transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand" style="border-left: 4px solid {{ $accent }}">
                        <time datetime="{{ $event->starts_at->toIso8601String() }}" class="flex w-16 flex-none flex-col items-center justify-center rounded-md bg-gray-100 py-2 text-center" aria-label="{{ $event->shortDateLabel() }}">
                            <span class="text-2xl font-bold leading-none text-ink">{{ $event->starts_at->format('j') }}</span>
                            <span class="mt-1 text-xs font-bold uppercase tracking-wide text-muted">{{ $event->starts_at->locale('pl')->translatedFormat('M') }}</span>
                        </time>
                        <span class="min-w-0 flex-1">
                            <span class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs font-bold uppercase tracking-widest text-muted">
                                <span><i class="fa-solid {{ $event->typeIcon() }} mr-1" aria-hidden="true"></i>{{ $event->typeLabel() }}</span>
                                <span><i class="fa-solid fa-location-dot mr-1" aria-hidden="true"></i>{{ $event->modeLabel() }}</span>
                                @if ($event->is_featured)<span class="rounded bg-ink px-2 py-0.5 text-white"><i class="fa-solid fa-star mr-1" aria-hidden="true"></i>Polecane</span>@endif
                            </span>
                            <span class="mt-1 block text-xl font-bold leading-snug text-ink group-hover:text-brand-dark">{{ $event->title }}</span>
                            @if ($event->lead)<span class="mt-1 line-clamp-2 block text-base leading-relaxed text-muted">{{ $event->lead }}</span>@endif
                            <span class="mt-2 block text-sm font-bold text-ink">{{ $event->shortDateLabel() }}</span>
                        </span>
                        <span class="flex flex-none items-center text-lg text-brand-dark transition group-hover:translate-x-1" aria-hidden="true">→</span>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</section>
