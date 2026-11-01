{{-- „Współpracujemy”: logotypy partnerów (moduł partners), każdy z nazwą w opisie alternatywnym. Pusty blok się nie pokazuje. --}}
@php $partners ??= collect(); $partners = $partners->filter(fn ($p) => $p->logo_url); @endphp
@if ($partners->isNotEmpty())
    <section class="fis-sec fis-sec--tint" aria-labelledby="fis-partners-h">
        <div class="fis-wrap">
            <h2 id="fis-partners-h">Współpracujemy</h2>
            <ul class="fis-partners" role="list">
                @foreach ($partners as $partner)
                    <li>
                        @if ($partner->url)
                            <a href="{{ $partner->url }}" target="_blank" rel="noopener"><img src="{{ $partner->logo_url }}" alt="{{ $partner->name }}" loading="lazy"><span class="sr-only"> (otwiera się w nowej karcie)</span></a>
                        @else
                            <img src="{{ $partner->logo_url }}" alt="{{ $partner->name }}" loading="lazy">
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
@endif
