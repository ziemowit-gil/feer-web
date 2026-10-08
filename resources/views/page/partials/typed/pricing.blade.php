{{-- Typ „Cennik i opłaty": karty cen pogrupowane (opcjonalne grupy), zasady płatności i przycisk. Dane: $page->typeData(). --}}
@php
    $td = $page->typeData();
    $rows = collect($td['price_rows'])->filter(fn ($r) => filled($r['item'] ?? null));
    $groups = $rows->groupBy(fn ($r) => trim((string) ($r['group'] ?? '')));
    $payParas = array_values(array_filter(preg_split('/\R{2,}/', trim((string) ($td['payment_info'] ?? '')))));
    $hasCta = filled($td['cta_label'] ?? null) && filled($td['cta_url'] ?? null);
@endphp
@once
    <style>
        .tp-grid { list-style: none; margin: 0 0 2rem; padding: 0; display: grid; gap: .75rem; grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr)); }
        .tp-card { display: flex; flex-direction: column; gap: .35rem; min-height: 8rem; padding: 1rem 1.1rem; border: 2px solid #d1d5db; border-radius: .5rem; background: #f9fafb; }
        .tp-item { font-size: 1.05rem; font-weight: 800; line-height: 1.3; color: #1d1d1a; } .tp-note { font-size: .92rem; line-height: 1.45; color: #1d1d1a; }
        .tp-amount { margin-top: auto; padding-top: .5rem; font-size: 1.6rem; font-weight: 800; line-height: 1.1; color: var(--color-brand); }
        .tp-pay { margin: 0 0 2rem; padding: 1rem 4.5rem 1rem 1.25rem; position: relative; border: 2px solid var(--color-brand); border-radius: .5rem; background: #fff; font-weight: 600; line-height: 1.6; }
        .tp-pay p { margin: 0 0 .6rem; } .tp-pay p:last-child { margin-bottom: 0; }
        .tp-pay-ico { position: absolute; top: 50%; right: 1rem; transform: translateY(-50%); display: flex; align-items: center; justify-content: center; width: 2.5rem; height: 2.5rem; border: 2px solid var(--color-brand); border-radius: 9999px; color: var(--color-brand); background: #fff; }
        .tp-cta { display: inline-flex; align-items: center; gap: .6rem; min-height: 3rem; padding: .6rem 1.4rem; border-radius: .375rem; background: var(--color-brand); color: #fff; font-weight: 800; text-decoration: none; border: 2px solid var(--color-brand); }
        .tp-cta:hover { background: #1d1d1a; border-color: #1d1d1a; } .tp-cta:focus-visible { outline: 3px solid #1d1d1a; outline-offset: 3px; }
    </style>
@endonce
<section class="mx-auto max-w-5xl px-4 py-8">
    @include('page.partials.typed._head')

    @forelse ($groups as $gName => $items)
        @php $gid = 'tp-g-'.\Illuminate\Support\Str::random(5); @endphp
        <section @if ($gName !== '') aria-labelledby="{{ $gid }}" @endif>
            @if ($gName !== '')<h2 id="{{ $gid }}" class="mb-3 mt-8 border-l-4 border-brand pl-3 text-2xl font-bold text-ink">{{ $gName }}</h2>@endif
            <ul role="list" class="tp-grid">
                @foreach ($items as $r)
                    <li class="tp-card">
                        <span class="tp-item">{{ $r['item'] }}</span>
                        @if (filled($r['note'] ?? null))<span class="tp-note">{{ $r['note'] }}</span>@endif
                        @if (filled($r['price'] ?? null))<span class="tp-amount"><span class="sr-only">Cena: </span>{{ $r['price'] }}</span>@endif
                    </li>
                @endforeach
            </ul>
        </section>
    @empty
        <p class="text-muted">Cennik zostanie wkrótce uzupełniony.</p>
    @endforelse

    @if ($payParas)
        <section aria-labelledby="tp-pay-h">
            <h2 id="tp-pay-h" class="mb-3 mt-8 border-l-4 border-brand pl-3 text-2xl font-bold text-ink">Jak płacić</h2>
            <div class="tp-pay">
                @foreach ($payParas as $para)<p>{!! nl2br(e($para)) !!}</p>@endforeach
                <span class="tp-pay-ico" aria-hidden="true"><i class="fa-solid fa-coins"></i></span>
            </div>
        </section>
    @endif

    @if ($hasCta)
        <p class="mt-6"><a href="{{ $td['cta_url'] }}" class="tp-cta">{{ $td['cta_label'] }} <span aria-hidden="true">→</span></a></p>
    @endif

    @include('partials.attachments-list', ['attachments' => $page->attachments])
</section>
