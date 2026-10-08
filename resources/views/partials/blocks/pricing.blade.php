{{-- Blok „Cennik" ([blok:ID]): karty cen w ramce (nazwa, opis, duża cena). Zmienne: $block (ContentBlock). --}}
@if (! request()->attributes->get('pr_css'))
    @php request()->attributes->set('pr_css', true); @endphp
    <style>
        .pr-frame { margin: 1.5rem 0; padding: 1.25rem 1.5rem; border: 2px solid var(--color-brand); border-radius: .5rem; background: #fff; }
        .pr-h { display: flex; align-items: center; gap: .6rem; margin: 0 0 .9rem; font-size: 1.25rem; font-weight: 800; color: #1d1d1a; }
        .pr-h i { display: inline-flex; width: 2rem; height: 2rem; align-items: center; justify-content: center; border: 2px solid var(--color-brand); border-radius: 9999px; color: var(--color-brand); font-size: .85rem; }
        .pr-grid { list-style: none; margin: 0; padding: 0; display: grid; gap: .75rem; grid-template-columns: repeat(auto-fit, minmax(13rem, 1fr)); }
        .pr-card { display: flex; flex-direction: column; gap: .35rem; min-height: 8rem; padding: 1rem 1.1rem; border: 2px solid #d1d5db; border-radius: .5rem; background: #f9fafb; }
        .pr-item { font-size: 1rem; font-weight: 800; line-height: 1.3; color: #1d1d1a; }
        .pr-note { font-size: .9rem; line-height: 1.45; color: #1d1d1a; }
        .pr-amount { margin-top: auto; padding-top: .5rem; font-size: 1.5rem; font-weight: 800; line-height: 1.1; color: var(--color-brand); }
    </style>
@endif
@php $d = $block->data ?? []; $hid = 'pr-h-'.$block->id.'-'.\Illuminate\Support\Str::random(4); @endphp
<section class="pr-frame not-prose" aria-labelledby="{{ $hid }}">
    <h2 id="{{ $hid }}" class="pr-h"><i class="fa-solid fa-coins" aria-hidden="true"></i> {{ $d['title'] ?? 'Cennik' }}</h2>
    <ul role="list" class="pr-grid">
        @foreach ($d['rows'] ?? [] as $row)
            <li class="pr-card">
                <span class="pr-item">{{ $row['item'] }}</span>
                @if (! empty($row['note']))<span class="pr-note">{{ $row['note'] }}</span>@endif
                @if (! empty($row['price']))<span class="pr-amount"><span class="sr-only">Cena: </span>{{ $row['price'] }}</span>@endif
            </li>
        @endforeach
    </ul>
</section>
