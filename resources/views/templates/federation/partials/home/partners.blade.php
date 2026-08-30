@if ($partners->isNotEmpty())
<section class="border-t border-gray-100 bg-gray-50 py-14" aria-labelledby="federation-partners-heading">
    <div class="mx-auto max-w-[1400px] px-4">
        <h2 id="federation-partners-heading" class="mb-8 text-center text-sm font-extrabold uppercase tracking-widest text-muted">
            Współpracujemy
        </h2>

        <ul class="flex flex-wrap items-center justify-center gap-3" role="list">
            @foreach ($partners as $partner)
                <li>
                    @if ($partner->url)
                        <a href="{{ $partner->url }}" target="_blank" rel="noopener"
                            class="flex items-center justify-center rounded-md border border-gray-200 bg-white px-4 py-3 text-center text-xs font-semibold text-muted transition hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                            aria-label="{{ $partner->name }}">
                            @if ($partner->logo_url)
                                <img src="{{ $partner->logo_url }}" alt="{{ $partner->name }}" class="h-8 w-auto max-w-[110px] object-contain">
                            @else
                                {{ $partner->name }}
                            @endif
                        </a>
                    @else
                        <span class="flex items-center justify-center rounded-md border border-gray-200 bg-white px-4 py-3 text-center text-xs font-semibold text-muted">
                            @if ($partner->logo_url)
                                <img src="{{ $partner->logo_url }}" alt="{{ $partner->name }}" class="h-8 w-auto max-w-[110px] object-contain">
                            @else
                                {{ $partner->name }}
                            @endif
                        </span>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
