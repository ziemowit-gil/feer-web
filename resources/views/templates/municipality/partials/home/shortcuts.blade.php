{{--
    Sekcja: Na skróty — siatka kafelków (5 kolumn × N wierszy).
    Dane: $shortcuts (kolekcja kafelków z page tiles_grid lub QuickAction).
--}}
@if ($shortcuts->isNotEmpty())
<section id="na-skroty" class="bg-white py-10" aria-label="Na skróty">
    <div class="mx-auto max-w-[1400px] px-4">
        <h2 class="mb-8 text-center text-3xl font-extrabold text-ink">Na skróty</h2>

        <ul class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5" role="list">
            @foreach ($shortcuts as $tile)
                @php
                    $tileUrl   = $tile['url']   ?? $tile->url   ?? '#';
                    $tileLabel = $tile['label'] ?? $tile->label ?? $tile->name ?? '';
                    $tileIcon  = $tile['icon']  ?? $tile->icon  ?? '';
                    $tileColor = $tile['color'] ?? $tile->color ?? null;
                    $tileImg   = $tile['image_url'] ?? $tile->image_url ?? null;
                    $isExternal = str_starts_with($tileUrl, 'http');
                    $hasHighlight = !empty($tile['highlight']) || !empty($tile->highlight);
                @endphp
                <li>
                    <a href="{{ $tileUrl }}"
                        @if ($isExternal) target="_blank" rel="noopener" @endif
                        class="group relative flex h-full flex-col items-center justify-between rounded p-4 text-center transition
                            {{ $hasHighlight
                                ? 'bg-[#e53935] text-white ring-2 ring-[#e53935]'
                                : 'bg-white text-ink ring-2 ring-[#e53935] hover:bg-gray-50' }}"
                        title="{{ $tileLabel . ($isExternal ? ' — Kliknij aby zobaczyć więcej informacji.' : '') }}"
                        aria-label="{{ $tileLabel }}">

                        {{-- Ikona lub obrazek --}}
                        <div class="mb-3 flex items-center justify-center">
                            @if ($tileImg)
                                <img src="{{ $tileImg }}" alt="" class="h-16 w-auto max-w-full object-contain">
                            @elseif ($tileIcon)
                                <i class="{{ $tileIcon }} text-4xl {{ $hasHighlight ? 'text-white' : 'text-brand' }}" aria-hidden="true"></i>
                            @else
                                <span class="flex h-14 w-14 items-center justify-center rounded-full bg-brand/10 text-2xl text-brand" aria-hidden="true">
                                    <i class="fa-solid fa-link"></i>
                                </span>
                            @endif
                        </div>

                        {{-- Etykieta --}}
                        <span class="text-xs font-extrabold uppercase tracking-wide leading-tight
                            {{ $hasHighlight ? 'text-white' : 'text-ink' }}">
                            {{ $tileLabel }}
                        </span>

                        {{-- Strzałka --}}
                        <span class="mt-2 text-lg font-bold
                            {{ $hasHighlight ? 'text-white' : 'text-[#e53935]' }}"
                            aria-hidden="true">&rsaquo;</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
