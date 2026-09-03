@if ($partners->isNotEmpty())
<section class="py-14" aria-labelledby="wrzos-members-heading">
    <div class="mx-auto max-w-[1400px] px-4">
        <h2 id="wrzos-members-heading" class="mb-8 border-b border-gray-200 pb-3 text-xl font-extrabold text-ink">
            <span class="border-t-2 border-brand pt-2">Organizacje</span> członkowskie
        </h2>

        <ul class="grid grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-6" role="list">
            @foreach ($partners as $partner)
                <li>
                    @if ($partner->url)
                        <a href="{{ $partner->url }}" target="_blank" rel="noopener"
                            class="flex h-20 items-center justify-center rounded-md border border-gray-100 bg-white p-3 transition hover:border-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                            aria-label="{{ $partner->name }}">
                            @if ($partner->logo_url)
                                <img src="{{ $partner->logo_url }}" alt="{{ $partner->name }}" class="h-full w-full object-contain">
                            @else
                                <span class="text-center text-xs font-bold text-muted">{{ $partner->name }}</span>
                            @endif
                        </a>
                    @else
                        <span class="flex h-20 items-center justify-center rounded-md border border-gray-100 bg-white p-3">
                            @if ($partner->logo_url)
                                <img src="{{ $partner->logo_url }}" alt="{{ $partner->name }}" class="h-full w-full object-contain">
                            @else
                                <span class="text-center text-xs font-bold text-muted">{{ $partner->name }}</span>
                            @endif
                        </span>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
