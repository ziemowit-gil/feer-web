@if ($siteSettings->isModuleEnabled('news'))
<section id="aktualnosci" class="mx-auto max-w-6xl px-4 py-12">

    @if ($siteSettings->homepageBannerIsVisible())
        <div role="region" aria-label="Ważna informacja"
            class="mb-8 flex items-start gap-3 rounded-xl border border-brand bg-brand-light px-5 py-4">
            <i class="fa-solid fa-circle-exclamation mt-0.5 flex-none text-xl text-brand" aria-hidden="true"></i>
            <div class="flex-1 text-sm">
                <p class="font-medium text-ink">{{ $siteSettings->homepage_banner_text }}</p>
                @if (filled($siteSettings->homepage_banner_link_label) && filled($siteSettings->homepage_banner_link_url))
                    <a href="{{ $siteSettings->homepage_banner_link_url }}"
                        class="mt-2 inline-flex items-center gap-1.5 font-bold text-brand underline-offset-2 hover:underline focus-visible:rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                        {{ $siteSettings->homepage_banner_link_label }}
                        <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
                    </a>
                @endif
            </div>
        </div>
    @endif

    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <h2 class="text-2xl font-bold text-ink">Aktualności</h2>
        <a href="{{ route('news.index') }}" class="rounded bg-brand px-4 py-2 text-xs font-bold uppercase tracking-wide text-white hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">Zobacz wszystkie</a>
    </div>

    <div class="grid gap-8 md:grid-cols-3">
        @forelse ($news as $item)
            <a href="{{ route('news.show', $item) }}"
                class="group block">
                @php $img = $item->imageUrlOrDefault(); @endphp
                @if ($img)
                    <div class="relative mb-3 h-32 overflow-hidden rounded-lg bg-gray-100">
                        <img src="{{ $img }}" alt="" class="h-full w-full object-cover transition group-hover:scale-105">
                        @if ($item->category)
                            <span class="absolute left-3 top-3 rounded px-2 py-1 text-xs font-bold uppercase text-white" style="background-color: {{ $item->category->badgeColor() }}">{{ $item->category->name }}</span>
                        @endif
                    </div>
                @endif
                <div class="text-xs font-medium text-muted">{{ $item->published_at->format('d.m.Y') }}</div>
                <h3 class="mt-1 font-bold text-ink group-hover:text-brand">{{ $item->title }}</h3>
                @if ($item->excerpt)
                    <p class="mt-1 text-sm text-muted">{{ $item->excerpt }}</p>
                @endif
            </a>
        @empty
            <p class="text-muted">Brak opublikowanych newsów.</p>
        @endforelse
    </div>
</section>
@endif
