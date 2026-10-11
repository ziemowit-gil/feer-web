{{--
    Typ „Partnerzy i sponsorzy": logotypy lub karty w grupach — partnerzy z modułu Partnerzy
    albo własne wpisy; przycisk „Zostań partnerem" i podziękowanie.
    Płasko: kafle na szarym tle bez obramowań, tylko kolory firmowe. Menu działu w kolumnie bocznej, gdy jest.
--}}
@php
    $td = $page->typeData();
    $layout = ($td['partners_layout'] ?? 'logos') === 'cards' ? 'cards' : 'logos';
    $partnersById = \App\Models\Partner::forCurrentSite()->orderBy('order')->orderBy('name')->get()->keyBy('id');

    // Wpisy: partner z modułu (nazwa i logo z modułu) albo własny wpis.
    $entries = collect($td['partners'])->map(function ($row) use ($partnersById) {
        $p = ! empty($row['partner_id']) ? $partnersById->get((int) $row['partner_id']) : null;
        $name = $p?->name ?: trim((string) ($row['name'] ?? ''));
        if ($name === '') {
            return null;
        }
        $url = trim((string) ($row['url'] ?? '')) ?: ($p?->url ?: null);
        return ['group' => trim((string) ($row['group'] ?? '')), 'name' => $name, 'url' => $url, 'logo' => trim((string) ($row['logo'] ?? '')) ?: ($p?->logo_url ?: null), 'note' => trim((string) ($row['note'] ?? '')), 'id' => $p?->id];
    })->filter()->values();

    if (! empty($td['partners_all'])) {
        $used = $entries->pluck('id')->filter()->all();
        foreach ($partnersById as $p) {
            if (! in_array($p->id, $used, true)) {
                $entries->push(['group' => 'Pozostali partnerzy', 'name' => $p->name, 'url' => $p->url ?: null, 'logo' => $p->logo_url, 'note' => '', 'id' => $p->id]);
            }
        }
    }

    // Kolejność grup: zdefiniowane w panelu, potem pozostałe wg pierwszego wystąpienia; wpisy bez grupy na początku.
    $groupMeta = collect($td['partner_groups'])->filter(fn ($g) => filled($g['title'] ?? null))->keyBy(fn ($g) => trim($g['title']));
    $order = array_values(array_unique(array_merge([''], $groupMeta->keys()->all(), $entries->pluck('group')->all())));
    $grouped = collect($order)->mapWithKeys(fn ($g) => [$g => $entries->where('group', $g)->values()])->filter(fn ($rows) => $rows->isNotEmpty());
    $ctaLabel = trim((string) ($td['partners_cta_label'] ?? '')); $ctaUrl = trim((string) ($td['partners_cta_url'] ?? ''));
    $ctaExternal = \Illuminate\Support\Str::startsWith($ctaUrl, 'http');
    $localNav = ($menuSiblings ?? collect())->isNotEmpty();
    $link = 'font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
@endphp
<section class="mx-auto max-w-6xl px-4 py-12" data-page-type="partners">
    @include('page.partials.typed._head')

    <div class="grid gap-10 {{ $localNav ? 'lg:grid-cols-[minmax(0,1fr)_22rem]' : '' }}">
        @if ($localNav)
            <aside class="lg:order-last" aria-label="Menu działu">
                <div class="lg:sticky lg:top-24">@include('partials.page-local-nav', ['menuSiblings' => $menuSiblings])</div>
            </aside>
        @endif

        <div class="min-w-0">
            @if ($page->content)
                <div class="prose mb-8 max-w-none text-ink">{!! \App\Support\ShortcodeParser::render($page->content) !!}</div>
            @endif

            @if ($grouped->isEmpty())
                <p class="text-muted">Lista partnerów zostanie wkrótce uzupełniona.</p>
            @endif

            @foreach ($grouped as $gName => $rows)
                @php $gid = 'pn-g-'.$loop->index; $meta = $groupMeta->get($gName); @endphp
                <section class="{{ $loop->first ? '' : 'mt-10' }}" @if ($gName !== '') aria-labelledby="{{ $gid }}" @else aria-label="Partnerzy" @endif>
                    @if ($gName !== '')
                        <h2 id="{{ $gid }}" class="mb-2 text-2xl font-bold text-ink">{{ $gName }}</h2>
                        @if (filled($meta['text'] ?? null))<p class="mb-4 max-w-3xl text-ink">{{ $meta['text'] }}</p>@endif
                    @endif
                    @if ($layout === 'logos')
                        <ul class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-[repeat(auto-fill,minmax(11rem,1fr))]" role="list">
                            @foreach ($rows as $e)
                                @php $inner = $e['logo'] ? '<img src="'.e($e['logo']).'" alt="'.e($e['name']).'" loading="lazy" class="max-h-[4.5rem] w-auto max-w-full object-contain">' : e($e['name']); @endphp
                                <li>
                                    @if ($e['url'])
                                        <a href="{{ $e['url'] }}" class="flex h-full min-h-28 items-center justify-center rounded-md bg-gray-50 p-4 text-center font-bold text-ink transition hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2" target="_blank" rel="noopener">{!! $inner !!}<span class="sr-only"> (otwiera się w nowej karcie)</span></a>
                                    @else
                                        <span class="flex h-full min-h-28 items-center justify-center rounded-md bg-gray-50 p-4 text-center font-bold text-ink">{!! $inner !!}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <ul class="mt-4 grid gap-4 sm:grid-cols-2 {{ $localNav ? '' : 'lg:grid-cols-3' }}" role="list">
                            @foreach ($rows as $e)
                                <li class="flex flex-col gap-3 rounded-md bg-gray-50 p-5">
                                    @if ($e['logo'])<div class="flex h-[4.5rem] items-center"><img src="{{ $e['logo'] }}" alt="" loading="lazy" class="max-h-[4.5rem] max-w-48 object-contain"></div>@endif
                                    <h3 class="text-lg font-bold text-ink">{{ $e['name'] }}</h3>
                                    @if ($e['note'] !== '')<p class="text-ink">{{ $e['note'] }}</p>@endif
                                    @if ($e['url'])<p class="mt-auto"><a href="{{ $e['url'] }}" class="{{ $link }}" target="_blank" rel="noopener">Strona partnera<span class="sr-only">: {{ $e['name'] }} (otwiera się w nowej karcie)</span> <i class="fa-solid fa-arrow-up-right-from-square text-sm" aria-hidden="true"></i></a></p>@endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endforeach

            @if ($ctaLabel !== '' && $ctaUrl !== '' || filled($td['partners_thanks'] ?? null))
                <div class="mt-12 flex flex-wrap items-center justify-between gap-4 rounded-md bg-brand-light p-6">
                    <p class="max-w-2xl text-lg font-bold text-ink">{{ filled($td['partners_thanks'] ?? null) ? $td['partners_thanks'] : 'Chcesz wesprzeć nasze działania jako partner lub sponsor?' }}</p>
                    @if ($ctaLabel !== '' && $ctaUrl !== '')
                        <a href="{{ $ctaUrl }}" class="inline-flex min-h-12 items-center gap-2 rounded-md bg-brand-dark px-6 text-base font-bold text-white transition hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2" @if ($ctaExternal) target="_blank" rel="noopener" @endif>
                            {{ $ctaLabel }}<i class="fa-solid fa-arrow-right text-sm" aria-hidden="true"></i>@if ($ctaExternal)<span class="sr-only"> (otwiera się w nowej karcie)</span>@endif
                        </a>
                    @endif
                </div>
            @endif

            @include('partials.attachments-list', ['attachments' => $page->attachments])
        </div>
    </div>
</section>
