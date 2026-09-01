{{-- Mapa „Nasze lokalizacje" — ośrodki KraFOS, zarządzane z panelu (Mapa pomocy). --}}
@php
    $sectionClass = match ($sectionStyle ?? 'plain') {
        'card' => 'h-full scroll-mt-24 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm',
        'bare' => 'scroll-mt-24',
        default => 'mt-12 scroll-mt-24 border-t border-gray-100 pt-8',
    };
@endphp
@if ($siteSettings->site_template === 'federation' && $siteSettings->isModuleEnabled('help_map'))
    @php $locations = \App\Models\HelpPoint::where('is_published', true)->orderBy('order')->orderBy('name')->get(); @endphp
    @if ($locations->isNotEmpty())
        <div id="nasze-lokalizacje" class="{{ $sectionClass }}" x-data="{ showList: false }">
            <h2 class="mb-2 text-xl font-bold text-ink">Nasze lokalizacje</h2>
            <p class="mb-5 max-w-2xl text-sm text-muted">
                Ośrodki {{ $siteSettings->siteNameGenitive() }} nie tylko w Krakowie, ale w całej Małopolsce.
                <a href="{{ route('help-map.index') }}" class="font-semibold text-brand underline-offset-2 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                    Zobacz pełną mapę pomocy
                </a>
            </p>

            <button type="button" @click="showList = ! showList" :aria-expanded="showList" aria-controls="nasze-lokalizacje-lista"
                class="mb-3 inline-flex items-center gap-1.5 text-sm font-semibold text-brand hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                <i class="fa-solid fa-list-ul" aria-hidden="true"></i>
                <span x-text="showList ? 'Ukryj listę tekstową lokalizacji' : 'Pokaż listę tekstową lokalizacji'"></span>
            </button>

            <div id="help-map"
                class="h-72 w-full rounded-lg"
                data-points="{{ $locations->map(fn ($p) => [
                    'id' => $p->id, 'name' => $p->name, 'category' => $p->category,
                    'address' => $p->address, 'phone' => $p->phone, 'url' => $p->url,
                    'lat' => (float) $p->lat, 'lng' => (float) $p->lng,
                ])->toJson() }}"
                role="img" aria-label="Mapa lokalizacji {{ $siteSettings->siteNameGenitive() }} w Małopolsce">
            </div>

            <ul id="nasze-lokalizacje-lista" x-show="showList" x-cloak class="mt-4 space-y-2 border-t border-gray-100 pt-4" role="list">
                @foreach ($locations as $location)
                    <li class="flex items-start gap-2 text-sm text-ink">
                        <i class="fa-solid fa-location-dot mt-0.5 text-xs text-brand" aria-hidden="true"></i>
                        <span>
                            <span class="font-semibold">{{ $location->name }}</span>
                            @if ($location->address)
                                <span class="text-muted"> — {{ $location->address }}</span>
                            @endif
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
@endif
