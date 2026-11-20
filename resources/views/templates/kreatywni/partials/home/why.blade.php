{{-- Dlaczego my: wyróżniki z ikoną „✓” i ciemna ramka „Jak pracujemy”. --}}
@php $kcWhy = $texts->rows('why_items', 2); @endphp
@if ($kcWhy !== [] || $texts->get('how_text') !== '')
<section id="dlaczego-my" class="kc-sec" aria-labelledby="kc-why-title">
    <div class="kc-wrap">
        <h2 id="kc-why-title" class="kc-h2 kc-head">{{ $texts->get('why_title') }}</h2>
        @if ($kcWhy !== [])
            <ul class="kc-grid kc-grid-4 kc-why">
                @foreach ($kcWhy as [$title, $text])
                    <li>
                        <span class="kc-check" aria-hidden="true"><i class="fa-solid fa-check"></i></span>
                        <div>
                            <h3>{{ $title }}</h3>
                            @if ($text !== '')<p>{{ $text }}</p>@endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
        @if ($texts->get('how_text') !== '')
            <div class="kc-how kc-dark">
                <h3>{{ $texts->get('how_title') }}</h3>
                <div>
                    @foreach ($texts->paragraphs('how_text') as $p)
                        <p>{{ $p }}</p>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</section>
@endif
