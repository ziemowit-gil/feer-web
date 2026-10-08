{{-- Blok „Akordeon" ([blok:ID]): natywne <details>/<summary> (obsługa klawiatury i czytników ekranu bez JS). Zmienne: $block. --}}
@php
    $d = $block->data ?? [];
    $group = ! empty($d['exclusive']) ? 'acc-'.$block->id : null;
@endphp
<section class="not-prose my-8" aria-label="{{ $d['title'] ?: $block->name }}">
    @if (! empty($d['title']))
        <h2 class="mb-3 text-2xl font-bold text-ink">{{ $d['title'] }}</h2>
    @endif
    <div class="divide-y divide-gray-300 border-y border-gray-300">
        @foreach ($d['items'] ?? [] as $i => $item)
            <details class="group" @if ($group) name="{{ $group }}" @endif @if ($i === 0 && ! empty($d['first_open'])) open @endif>
                <summary class="flex min-h-12 cursor-pointer list-none items-center justify-between gap-4 py-3 text-lg font-bold text-ink hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 [&::-webkit-details-marker]:hidden">
                    <span>{{ $item['q'] }}</span>
                    <i class="fa-solid fa-chevron-down flex-none text-sm transition-transform motion-reduce:transition-none group-open:rotate-180" aria-hidden="true"></i>
                </summary>
                <div class="pb-5 pr-8 text-base leading-relaxed text-ink">{!! nl2br(e($item['a'])) !!}</div>
            </details>
        @endforeach
    </div>
</section>
