{{-- Elementy pod menu sekcji działania: krótka notka i przyciski (ustawiane w formularzu działania). --}}
@php $extraButtons = collect($project->sidebar_buttons ?? []); @endphp
@if ($extraButtons->isNotEmpty() || filled($project->sidebar_note))
    <div class="mt-5 space-y-3">
        @if (filled($project->sidebar_note))
            <p class="text-base text-ink">{!! nl2br(e($project->sidebar_note)) !!}</p>
        @endif
        {{-- Przyciski płaskie, bez obwódek: „wypełniony" (styl primary) — biały tekst na brand-dark, zwykły — ciemny tekst na białym polu; strzałka po prawej. --}}
        @foreach ($extraButtons as $btn)
            @php $filled = ($btn['style'] ?? 'outline') === 'primary'; @endphp
            <a href="{{ $btn['url'] }}" @if (! empty($btn['new_tab'])) target="_blank" rel="noopener" @endif
               class="group flex min-h-14 items-center gap-3 rounded-md px-4 py-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2 {{ $filled ? 'bg-brand-dark text-white hover:bg-ink' : 'bg-white text-ink hover:bg-brand-light' }}">
                <span class="min-w-0 flex-1 text-base font-bold leading-snug">{{ $btn['label'] }}</span>
                @if (! empty($btn['new_tab']))<span class="sr-only">(otwiera się w nowej karcie)</span>@endif
                <span class="flex-none text-xl transition group-hover:translate-x-1 motion-reduce:transition-none" aria-hidden="true">→</span>
            </a>
        @endforeach
    </div>
@endif
