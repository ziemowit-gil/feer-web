@php
    $sidebarActive = $siteSettings->header_layout === 'wide_mission' && ($siteSettings->wide_mission_sidebar ?? false);
    $quickLinksHere = ($sidebarActive && $quickLinks->isNotEmpty()) ? collect() : $quickLinks;
@endphp
@if ($poll || $quickLinksHere->isNotEmpty())
<section class="border-t border-gray-100 bg-gray-50">
    <div class="mx-auto max-w-6xl px-4 py-12 {{ $poll ? 'grid gap-10 md:grid-cols-2' : '' }}">
        @if ($poll)
            <div id="ankieta">
                <h2 class="mb-4 text-xl font-bold text-ink">Ankieta</h2>

                @php
                    $votedKey = "voted_polls.{$poll->id}";
                    $votedOptionId = session($votedKey);
                    $totalVotes = $poll->totalVotes();
                @endphp

                <p class="mb-4 text-sm text-ink">{{ $poll->question }}</p>

                <form action="{{ route('polls.vote', $poll) }}" method="POST" class="space-y-3">
                    @csrf
                    @foreach ($poll->options as $i => $option)
                        <label class="block {{ $votedOptionId ? '' : 'cursor-pointer' }}">
                            <div class="flex items-center gap-2">
                                <input type="radio" name="option_id" value="{{ $option->id }}"
                                    {{ $votedOptionId ? ($votedOptionId == $option->id ? 'checked' : 'disabled') : ($i === 0 ? 'checked' : '') }}
                                    class="accent-brand">
                                <span class="text-sm text-ink">{{ $option->label }} ({{ $option->percent($totalVotes) }}%)</span>
                            </div>
                            <div class="ml-6 mt-1 h-2 w-full max-w-xs overflow-hidden rounded-full bg-gray-200">
                                <div class="h-full rounded-full bg-brand" style="width: {{ $option->percent($totalVotes) }}%"></div>
                            </div>
                        </label>
                    @endforeach

                    @if ($votedOptionId)
                        <p class="text-xs font-bold text-muted">Dziękujemy za oddanie głosu.</p>
                    @else
                        <button type="submit" class="mt-2 rounded bg-brand px-6 py-2 text-xs font-bold uppercase tracking-wide text-white hover:bg-brand-dark">Głosuj</button>
                    @endif
                </form>
            </div>
        @endif

        @if ($quickLinksHere->isNotEmpty())
            <div>
                <h2 class="mb-4 text-xl font-bold text-ink">Na skróty</h2>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                    @foreach ($quickLinksHere as $link)
                        @php
                            $hasColor  = \App\Support\Color::isValid($link->color);
                            $qa        = $hasColor ? \App\Support\Color::button($link->color) : null;
                            $bgColor   = $hasColor ? $qa['bg'] : 'var(--color-brand)';
                            $fgColor   = $hasColor ? $qa['text'] : '#ffffff';
                            $isNeg     = (bool) $link->is_negative;
                            $isStrip   = (bool) $link->strip;
                            $colSpan   = $link->colSpanClass();

                            // Klasy bazowe wspólne dla każdego wariantu
                            $base = 'rounded-lg shadow-sm transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 ' . $colSpan;
                        @endphp

                        @if ($isStrip)
                            {{-- ════ PASEK: ikona obok tekstu ════════════════════════ --}}
                            <a href="{{ $link->url }}"
                                class="{{ $base }} flex items-center gap-4 px-5 py-4 hover:shadow-md
                                    @if ($isNeg) hover:opacity-90 @else border-2 border-gray-200 bg-white hover:border-brand @endif"
                                @if ($isNeg)
                                    style="background-color: {{ $bgColor }}; color: {{ $fgColor }};"
                                @endif>
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-xl"
                                    @if ($isNeg)
                                        style="background-color: rgba(255,255,255,0.2);"
                                    @elseif ($hasColor)
                                        style="background-color: {{ $bgColor }}; color: {{ $fgColor }};"
                                    @else
                                        class="bg-brand-light text-brand"
                                    @endif>
                                    <i class="bi {{ $link->icon }}" aria-hidden="true"></i>
                                </span>
                                <span class="text-sm font-bold @if (!$isNeg) text-ink @endif">{{ $link->label }}</span>
                                <i class="fa-solid fa-chevron-right ml-auto text-[0.65rem] opacity-40" aria-hidden="true"></i>
                            </a>

                        @elseif ($isNeg)
                            {{-- ════ KARTA NEGATYW: pełne tło kolorem ════════════════ --}}
                            <a href="{{ $link->url }}"
                                class="{{ $base }} flex flex-col items-center gap-2 px-4 py-6 text-center hover:opacity-90 hover:shadow-md"
                                style="background-color: {{ $bgColor }}; color: {{ $fgColor }};">
                                <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full text-2xl"
                                    style="background-color: rgba(255,255,255,0.2);">
                                    <i class="bi {{ $link->icon }}" aria-hidden="true"></i>
                                </span>
                                <span class="text-sm font-bold">{{ $link->label }}</span>
                            </a>

                        @elseif ($hasColor)
                            {{-- ════ KARTA Z KOLOREM: biały kafelek z kolorową ikoną ═ --}}
                            <a href="{{ $link->url }}"
                                class="{{ $base }} flex flex-col items-center gap-2 border-2 border-gray-200 bg-white px-4 py-6 text-center hover:shadow-md"
                                onmouseover="this.style.borderColor='{{ $bgColor }}'" onmouseout="this.style.borderColor=''">
                                <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full text-2xl"
                                    style="background-color: {{ $bgColor }}; color: {{ $fgColor }};">
                                    <i class="bi {{ $link->icon }}" aria-hidden="true"></i>
                                </span>
                                <span class="text-sm font-bold text-ink">{{ $link->label }}</span>
                            </a>

                        @else
                            {{-- ════ KARTA DOMYŚLNA: kolor marki ════════════════════ --}}
                            <a href="{{ $link->url }}"
                                class="{{ $base }} flex flex-col items-center gap-2 border-2 border-gray-200 bg-white px-4 py-6 text-center hover:border-brand hover:text-brand hover:shadow-md">
                                <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-brand-light text-2xl text-brand">
                                    <i class="bi {{ $link->icon }}" aria-hidden="true"></i>
                                </span>
                                <span class="text-sm font-bold">{{ $link->label }}</span>
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</section>
@endif
