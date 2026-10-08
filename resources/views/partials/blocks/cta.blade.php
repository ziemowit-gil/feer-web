{{-- Blok „Przyciski CTA" ([blok:ID]). Zmienne: $block (ContentBlock). Styl jak kafle „Na skróty": obwódka 2 px albo wypełnienie. --}}
@php
    $d = $block->data ?? [];
    $justify = ['left' => 'justify-start', 'center' => 'justify-center', 'right' => 'justify-end'][$d['align'] ?? 'left'] ?? 'justify-start';
@endphp
<div class="not-prose my-6 flex flex-wrap items-stretch gap-3 {{ $justify }}" role="group" aria-label="{{ $block->name }}">
    @foreach ($d['buttons'] ?? [] as $b)
        @php
            $accent = $siteSettings->contrastSafeColor($b['color'] ?? '#1e6dff');
            $filled = (bool) ($b['filled'] ?? true);
            $textColor = $filled ? \App\Support\Color::button($accent)['text'] : '#1d1d1a';
        @endphp
        <a href="{{ $b['url'] }}" @if (! empty($b['new_tab'])) target="_blank" rel="noopener" @endif
           class="group inline-flex min-h-12 items-center gap-3 rounded-md px-5 py-2 text-base font-bold no-underline transition hover:opacity-90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2"
           style="{{ $filled ? 'background-color: '.$accent.'; color: '.$textColor : 'background-color: #fff; border: 2px solid '.$accent.'; color: #1d1d1a' }}">
            <span>{{ $b['label'] }}</span>
            @if (! empty($b['new_tab']))<span class="sr-only">(otwiera się w nowej karcie)</span>@endif
            <span class="transition group-hover:translate-x-1 motion-reduce:transition-none motion-reduce:transform-none" aria-hidden="true">→</span>
        </a>
    @endforeach
</div>
