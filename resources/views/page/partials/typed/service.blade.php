{{--
    Typ „Oferta / usługa": lead → treść → dla kogo → korzyści → jak działamy → CTA + kontakt.
    Dane: $page->typeData() (kolumna type_data).
--}}
@php
    $td = $page->typeData();
    $hasCta = filled($td['cta_label'] ?? null) && filled($td['cta_url'] ?? null);
    $hasContact = filled($td['contact_name'] ?? null) || filled($td['contact_email'] ?? null) || filled($td['contact_phone'] ?? null);
@endphp

<section class="mx-auto max-w-5xl px-4 py-12">
    <div class="grid gap-10 {{ ($menuSiblings ?? collect())->isNotEmpty() ? 'md:grid-cols-[1fr_220px]' : '' }}">
        <div class="min-w-0">
            <span class="mb-4 inline-flex items-center gap-1.5 rounded-full bg-brand-light px-3 py-1 text-sm font-bold text-brand">
                <i class="fa-solid fa-briefcase" aria-hidden="true"></i> Oferta
            </span>
            <h1 class="mb-4 text-3xl font-bold text-ink md:text-4xl">{{ $page->title }}</h1>
            @if (filled($td['lead'] ?? null))
                <p class="mb-8 max-w-3xl text-lg leading-relaxed text-muted">{{ $td['lead'] }}</p>
            @endif

            @include('partials.page-content-image')

            @if ($page->content)
                <div class="prose max-w-none text-ink">@shortcodes($page->content)</div>
            @endif

            @if ($td['audience'])
                <section class="mt-10" aria-labelledby="service-audience">
                    <h2 id="service-audience" class="mb-4 text-2xl font-bold text-ink">Dla kogo</h2>
                    <ul class="grid gap-2 sm:grid-cols-2">
                        @foreach ($td['audience'] as $row)
                            <li class="flex items-start gap-3 rounded-lg border border-gray-200 px-4 py-3">
                                <i class="fa-solid fa-user-check mt-1 text-brand" aria-hidden="true"></i>
                                <span class="text-ink">{{ $row['text'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($td['benefits'])
                <section class="mt-10" aria-labelledby="service-benefits">
                    <h2 id="service-benefits" class="mb-4 text-2xl font-bold text-ink">Co zyskujesz</h2>
                    <ul class="grid gap-4 sm:grid-cols-2">
                        @foreach ($td['benefits'] as $row)
                            <li class="rounded-xl border border-gray-200 bg-white p-5">
                                <span class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-brand-light text-brand" aria-hidden="true">
                                    <i class="{{ filled($row['icon'] ?? null) ? $row['icon'] : 'fa-solid fa-check' }}"></i>
                                </span>
                                @if (filled($row['title'] ?? null))
                                    <h3 class="text-base font-bold text-ink">{{ $row['title'] }}</h3>
                                @endif
                                @if (filled($row['text'] ?? null))
                                    <p class="mt-1 text-sm leading-relaxed text-muted">{{ $row['text'] }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($td['steps'])
                <section class="mt-10" aria-labelledby="service-steps">
                    <h2 id="service-steps" class="mb-4 text-2xl font-bold text-ink">Jak działamy</h2>
                    <ol class="relative space-y-4 border-l-2 border-brand/30 pl-8">
                        @foreach ($td['steps'] as $i => $row)
                            <li class="relative">
                                <span class="absolute -left-[2.45rem] flex h-8 w-8 items-center justify-center rounded-full bg-brand text-sm font-bold text-white" aria-hidden="true">{{ $i + 1 }}</span>
                                <h3 class="text-base font-bold text-ink"><span class="sr-only">Etap {{ $i + 1 }}: </span>{{ $row['title'] ?? '' }}</h3>
                                @if (filled($row['text'] ?? null))
                                    <p class="mt-1 text-sm leading-relaxed text-muted">{{ $row['text'] }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif

            @if ($hasCta || $hasContact)
                <aside class="mt-12 rounded-2xl bg-brand-light p-6 md:p-8" aria-labelledby="service-cta">
                    <h2 id="service-cta" class="text-xl font-bold text-ink">Porozmawiajmy</h2>
                    @if ($hasContact)
                        <ul class="mt-3 space-y-1 text-sm text-ink">
                            @if (filled($td['contact_name'] ?? null))
                                <li class="font-bold">{{ $td['contact_name'] }}</li>
                            @endif
                            @if (filled($td['contact_email'] ?? null))
                                <li><a href="mailto:{{ $td['contact_email'] }}" class="text-brand underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"><i class="fa-solid fa-envelope mr-1.5" aria-hidden="true"></i>{{ $td['contact_email'] }}</a></li>
                            @endif
                            @if (filled($td['contact_phone'] ?? null))
                                <li><a href="tel:{{ preg_replace('/\s+/', '', $td['contact_phone']) }}" class="text-brand underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"><i class="fa-solid fa-phone mr-1.5" aria-hidden="true"></i>{{ $td['contact_phone'] }}</a></li>
                            @endif
                        </ul>
                    @endif
                    @if ($hasCta)
                        <a href="{{ $td['cta_url'] }}" class="mt-5 inline-flex min-h-11 items-center gap-2 rounded-full bg-brand px-6 text-sm font-bold text-white transition hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                            {{ $td['cta_label'] }} <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
                        </a>
                    @endif
                </aside>
            @endif

            @include('partials.page-gallery', ['page' => $page])
            @include('partials.attachments-list', ['attachments' => $page->attachments])
        </div>

        @if (($menuSiblings ?? collect())->isNotEmpty())
            @include('partials.page-local-nav', ['menuSiblings' => $menuSiblings])
        @endif
    </div>
</section>
