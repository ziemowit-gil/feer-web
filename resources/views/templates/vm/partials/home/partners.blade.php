{{-- „Współpraca" szablonu "vm": rząd logotypów partnerów (moduł Partnerzy). --}}
@if ($partners->isNotEmpty())
<section class="py-14" aria-labelledby="vm-partners-heading">
    <div class="mx-auto max-w-[1200px] px-4">
        <h2 id="vm-partners-heading" class="vm-display vm-section-title mb-10 text-2xl text-ink sm:text-3xl lg:text-4xl">Współpraca</h2>

        <ul class="flex flex-wrap items-center justify-center gap-x-10 gap-y-6" role="list">
            @foreach ($partners as $partner)
                <li>
                    @if ($partner->url)
                        <a href="{{ $partner->url }}" target="_blank" rel="noopener"
                           class="block rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-4"
                           aria-label="{{ $partner->name }} — otwiera się w nowej karcie">
                            @if ($partner->logo_url)
                                <img src="{{ $partner->logo_url }}" alt="" loading="lazy" class="h-14 w-auto max-w-[160px] object-contain">
                            @else
                                <span class="text-sm font-bold text-muted">{{ $partner->name }}</span>
                            @endif
                        </a>
                    @elseif ($partner->logo_url)
                        <img src="{{ $partner->logo_url }}" alt="{{ $partner->name }}" loading="lazy" class="h-14 w-auto max-w-[160px] object-contain">
                    @else
                        <span class="text-sm font-bold text-muted">{{ $partner->name }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
