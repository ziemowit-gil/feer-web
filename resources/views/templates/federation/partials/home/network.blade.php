@php $subsites ??= \App\Models\SiteSetting::current()->subsites()->orderBy('id')->get(); @endphp
@if ($subsites->isNotEmpty())
<section class="bg-gray-50 py-14" aria-labelledby="federation-network-heading">
    <div class="mx-auto max-w-[1400px] px-4">

        <div class="mb-8">
            <h2 id="federation-network-heading" class="text-2xl font-extrabold text-gray-900 md:text-3xl">
                Nasza sieć
            </h2>
            <p class="mt-1 text-sm text-gray-500">Ośrodki i punkty prowadzone przez {{ $siteSettings->site_name }}.</p>
        </div>

        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($subsites as $subsite)
            <a href="{{ current_site_url($subsite) }}" target="_blank" rel="noopener"
                class="group flex items-center gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100 transition hover:shadow-md">
                @if ($subsite->logoUrl())
                    <img src="{{ $subsite->logoUrl() }}" alt="" class="h-12 w-12 flex-none rounded-lg object-contain">
                @else
                    <span class="flex h-12 w-12 flex-none items-center justify-center rounded-lg text-lg font-bold text-white" style="background-color: {{ $subsite->brand_color ?: 'var(--color-brand)' }}">
                        {{ mb_substr($subsite->site_name, 0, 1) }}
                    </span>
                @endif
                <span class="min-w-0">
                    <span class="block truncate text-base font-extrabold text-gray-900 group-hover:text-brand">{{ $subsite->site_name }}</span>
                    @if ($subsite->tagline)
                        <span class="block truncate text-sm text-gray-500">{{ $subsite->tagline }}</span>
                    @endif
                </span>
                <i class="fa-solid fa-arrow-up-right-from-square ml-auto shrink-0 text-xs text-gray-300 group-hover:text-brand" aria-hidden="true"></i>
            </a>
            @endforeach
        </div>

    </div>
</section>
@endif
