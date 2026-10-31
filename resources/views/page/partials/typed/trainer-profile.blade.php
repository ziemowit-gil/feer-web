{{-- Typ „Trener”: biogram (treść strony), specjalizacje, kwalifikacje, kontakt i szkolenia, w których wpisano tego trenera. --}}
@php
    $td = $page->typeData();
    $myOffers = \App\Models\Page::forCurrentSite()->where('type', 'training_offer')->where('is_published', true)->orderBy('title')->get()
        ->filter(fn ($o) => collect($o->typeData()['trainers'] ?? [])->contains(fn ($t) => mb_strtolower(trim((string) ($t['name'] ?? ''))) === mb_strtolower(trim($page->title))));
    $meta = array_filter(['Rola' => $td['role'] ?? null, 'Języki' => $td['languages'] ?? null], 'filled');
    $hasContact = filled($td['contact_email'] ?? null) || filled($td['contact_phone'] ?? null);
@endphp

<section class="mx-auto max-w-6xl px-4 py-12" data-page-type="trainer_profile">
    @include('page.partials.typed._head', ['print' => false])
    <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <aside class="lg:order-last" aria-label="Trener: dane">
            @if ($page->content_image)
                <img src="{{ $page->content_image }}" alt="{{ $page->content_image_alt ?: $page->title }}" class="mb-6 aspect-square w-full rounded-md object-cover">
            @endif
            @if ($meta || $hasContact)
                <div class="rounded-md bg-brand-light p-6">
                    <dl class="grid gap-y-3">
                        @foreach ($meta as $label => $value)
                            <div><dt class="text-xs font-bold uppercase tracking-wide text-muted">{{ $label }}</dt><dd class="font-semibold text-ink">{{ $value }}</dd></div>
                        @endforeach
                    </dl>
                    @if ($hasContact)
                        <p class="{{ $meta ? 'mt-4' : '' }} text-sm text-ink">
                            @if (filled($td['contact_email'] ?? null))<a href="mailto:{{ $td['contact_email'] }}" class="block font-bold text-brand-dark underline underline-offset-4">{{ $td['contact_email'] }}</a>@endif
                            @if (filled($td['contact_phone'] ?? null))<a href="tel:{{ preg_replace('/[^0-9+]/', '', $td['contact_phone']) }}" class="block font-bold text-brand-dark underline underline-offset-4">{{ $td['contact_phone'] }}</a>@endif
                        </p>
                    @endif
                </div>
            @endif
        </aside>

        <div class="min-w-0">
            @if ($page->content)<div class="prose max-w-none text-ink">@shortcodes($page->content)</div>@endif

            @if ($td['specializations'])
                <section class="mt-10" aria-labelledby="tp-spec"><h2 id="tp-spec" class="mb-3 text-2xl font-bold text-ink">Specjalizacje</h2>
                    <ul class="flex flex-wrap gap-2" role="list">@foreach ($td['specializations'] as $row)<li class="rounded-md bg-gray-100 px-3 py-1.5 text-sm font-semibold text-ink">{{ $row['text'] ?? '' }}</li>@endforeach</ul>
                </section>
            @endif
            @if ($td['qualifications'])
                <section class="mt-10" aria-labelledby="tp-qual"><h2 id="tp-qual" class="mb-3 text-2xl font-bold text-ink">Kwalifikacje i doświadczenie</h2>
                    <ul class="list-disc space-y-1 pl-5 text-ink">@foreach ($td['qualifications'] as $row)<li>{{ $row['text'] ?? '' }}</li>@endforeach</ul>
                </section>
            @endif
            @if ($myOffers->isNotEmpty())
                <section class="mt-10" aria-labelledby="tp-offers"><h2 id="tp-offers" class="mb-3 text-2xl font-bold text-ink">Prowadzone szkolenia</h2>
                    <ul class="space-y-2" role="list">@foreach ($myOffers as $o)<li><a href="{{ $o->publicUrl() }}" class="font-bold text-brand-dark underline underline-offset-4 hover:no-underline">{{ $o->title }}</a></li>@endforeach</ul>
                </section>
            @endif
            @include('partials.attachments-list', ['attachments' => $page->attachments])
        </div>
    </div>
</section>
