{{-- Typ „Partnerzy i sponsorzy": logotypy lub karty w grupach — partnerzy z modułu Partnerzy albo własne wpisy; przycisk „Zostań partnerem" i podziękowanie. --}}
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
@endphp
<style>
    .pn-group { margin-top: 2.5rem; }
    .pn-h { margin: 0 0 .25rem; padding-left: .75rem; border-left: 4px solid var(--color-brand-dark); font-size: 1.5rem; font-weight: 800; color: #1d1d1a; }
    .pn-desc { margin: 0 0 1rem .75rem; color: #374151; }
    .pn-logos { list-style: none; margin: 0; padding: 0; display: grid; gap: 1rem; grid-template-columns: repeat(auto-fill, minmax(11rem, 1fr)); }
    .pn-logo { display: flex; min-height: 7rem; align-items: center; justify-content: center; padding: 1rem; border: 2px solid #d1d5db; border-radius: .5rem; background: #fff; color: #1d1d1a; text-decoration: none; text-align: center; font-weight: 800; }
    .pn-logo img { max-height: 4.5rem; max-width: 100%; width: auto; object-fit: contain; }
    a.pn-logo:hover { border-color: var(--color-brand-dark); } a.pn-logo:focus-visible { outline: 3px solid #1d1d1a; outline-offset: 3px; }
    .pn-cards { list-style: none; margin: 0; padding: 0; display: grid; gap: 1rem; grid-template-columns: repeat(auto-fill, minmax(16rem, 1fr)); }
    .pn-card { display: flex; flex-direction: column; gap: .75rem; padding: 1.25rem; border: 2px solid #d1d5db; border-radius: .5rem; background: #fff; }
    .pn-card-logo { display: flex; height: 4.5rem; align-items: center; } .pn-card-logo img { max-height: 4.5rem; max-width: 12rem; object-fit: contain; }
    .pn-card-name { margin: 0; font-size: 1.1rem; font-weight: 800; color: #1d1d1a; } .pn-card-note { margin: 0; color: #374151; }
    .pn-card a { font-weight: 800; color: var(--color-brand-dark); text-decoration: underline; text-underline-offset: 3px; } .pn-card a:hover { color: #1d1d1a; }
    .pn-cta { margin-top: 3rem; padding: 1.5rem; border-radius: .5rem; background: var(--color-brand-light); display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
    .pn-cta p { margin: 0; font-size: 1.1rem; font-weight: 700; color: #1d1d1a; max-width: 40rem; }
    .pn-btn { display: inline-flex; min-height: 3rem; align-items: center; gap: .5rem; padding: 0 1.5rem; border-radius: .375rem; background: var(--color-brand-dark); color: #fff; font-weight: 800; text-decoration: none; }
    .pn-btn:hover { background: #1d1d1a; } .pn-btn:focus-visible { outline: 3px solid #1d1d1a; outline-offset: 3px; }
</style>
<section class="mx-auto max-w-6xl px-4 py-8">
    @include('page.partials.typed._head')

    @if ($grouped->isEmpty())
        <p class="text-muted">Lista partnerów zostanie wkrótce uzupełniona.</p>
    @endif

    @foreach ($grouped as $gName => $rows)
        @php $gid = 'pn-g-'.$loop->index; $meta = $groupMeta->get($gName); @endphp
        <section class="pn-group" @if ($gName !== '') aria-labelledby="{{ $gid }}" @else aria-label="Partnerzy" @endif>
            @if ($gName !== '')
                <h2 id="{{ $gid }}" class="pn-h">{{ $gName }}</h2>
                @if (filled($meta['text'] ?? null))<p class="pn-desc">{{ $meta['text'] }}</p>@endif
            @endif
            @if ($layout === 'logos')
                <ul class="pn-logos" role="list">
                    @foreach ($rows as $e)
                        @php $inner = $e['logo'] ? '<img src="'.e($e['logo']).'" alt="'.e($e['name']).'" loading="lazy">' : e($e['name']); @endphp
                        <li>
                            @if ($e['url'])
                                <a href="{{ $e['url'] }}" class="pn-logo" target="_blank" rel="noopener">{!! $inner !!}<span class="sr-only"> (otwiera się w nowej karcie)</span></a>
                            @else
                                <span class="pn-logo">{!! $inner !!}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @else
                <ul class="pn-cards" role="list">
                    @foreach ($rows as $e)
                        <li class="pn-card">
                            @if ($e['logo'])<div class="pn-card-logo"><img src="{{ $e['logo'] }}" alt="" loading="lazy"></div>@endif
                            <h3 class="pn-card-name">{{ $e['name'] }}</h3>
                            @if ($e['note'] !== '')<p class="pn-card-note">{{ $e['note'] }}</p>@endif
                            @if ($e['url'])<p style="margin:auto 0 0"><a href="{{ $e['url'] }}" target="_blank" rel="noopener">Strona partnera<span class="sr-only">: {{ $e['name'] }} (otwiera się w nowej karcie)</span> →</a></p>@endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    @endforeach

    @if ($ctaLabel !== '' && $ctaUrl !== '' || filled($td['partners_thanks'] ?? null))
        <div class="pn-cta">
            <p>{{ filled($td['partners_thanks'] ?? null) ? $td['partners_thanks'] : 'Chcesz wesprzeć nasze działania jako partner lub sponsor?' }}</p>
            @if ($ctaLabel !== '' && $ctaUrl !== '')<a href="{{ $ctaUrl }}" class="pn-btn">{{ $ctaLabel }}<i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>@endif
        </div>
    @endif

    @include('partials.attachments-list', ['attachments' => $page->attachments])
</section>
