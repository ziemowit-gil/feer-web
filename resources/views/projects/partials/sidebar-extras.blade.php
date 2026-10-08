{{-- Elementy pod menu sekcji projektu: krótka notka i przyciski (ustawiane w formularzu projektu). --}}
@php $extraButtons = collect($project->sidebar_buttons ?? []); @endphp
@if ($extraButtons->isNotEmpty() || filled($project->sidebar_note))
    <div class="mt-5 space-y-3">
        @if (filled($project->sidebar_note))
            <p class="text-base text-ink">{!! nl2br(e($project->sidebar_note)) !!}</p>
        @endif
        @foreach ($extraButtons as $btn)
            <a href="{{ $btn['url'] }}" @if (! empty($btn['new_tab'])) target="_blank" rel="noopener" @endif
               @class([
                   'flex w-full items-center justify-center gap-2 rounded px-4 py-3 text-center text-base font-bold transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand',
                   'bg-brand text-white hover:bg-brand-dark' => ($btn['style'] ?? 'primary') !== 'outline',
                   'border-2 border-brand bg-white text-brand hover:bg-brand hover:text-white' => ($btn['style'] ?? '') === 'outline',
               ])>
                {{ $btn['label'] }}
                @if (! empty($btn['new_tab']))<span class="sr-only">(otwiera się w nowej karcie)</span>@endif
            </a>
        @endforeach
    </div>
@endif
