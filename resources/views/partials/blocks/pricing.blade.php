{{-- Blok „Cennik" ([blok:ID]): tabela cen (pozycja, opis, cena) w ramce; opcjonalny podział na kategorie (pole group). Zmienne: $block (ContentBlock). --}}
@if (! request()->attributes->get('pr_css'))
    @php request()->attributes->set('pr_css', true); @endphp
    <style>
        .pr-frame { margin: 1.5rem 0; padding: 1.25rem 1.5rem; border: 2px solid var(--color-brand); border-radius: .5rem; background: #fff; }
        .pr-h { display: flex; align-items: center; gap: .6rem; margin: 0 0 .9rem; font-size: 1.25rem; font-weight: 800; color: #1d1d1a; }
        .pr-h i { display: inline-flex; width: 2rem; height: 2rem; align-items: center; justify-content: center; border: 2px solid var(--color-brand); border-radius: 9999px; color: var(--color-brand); font-size: .85rem; }
        .pr-sub { margin: 1.25rem 0 .5rem; font-size: 1.05rem; font-weight: 800; color: #1d1d1a; border-left: 4px solid var(--color-brand); padding-left: .6rem; }
        .pr-table-wrap { overflow-x: auto; border: 2px solid #d1d5db; border-radius: .5rem; }
        .pr-table { width: 100%; border-collapse: collapse; font-size: 1rem; color: #1d1d1a; }
        .pr-table th, .pr-table td { padding: .65rem .9rem; text-align: left; vertical-align: top; border-bottom: 1px solid #d1d5db; }
        .pr-table thead th { background: #f9fafb; font-weight: 800; border-bottom: 2px solid var(--color-brand); }
        .pr-table tbody tr:last-child td, .pr-table tbody tr:last-child th { border-bottom: 0; }
        .pr-table .pr-item { font-weight: 700; }
        .pr-table .pr-note { font-size: .92rem; line-height: 1.45; }
        .pr-table .pr-amount { font-weight: 800; white-space: nowrap; }
        @media print {
            .pr-frame { border-color: #000; background: none; }
            .pr-table-wrap { overflow: visible; border: 1px solid #000; border-radius: 0; }
            .pr-table { font-size: 11pt; }
            .pr-table th, .pr-table td { padding: .4rem .6rem; border-bottom: 1px solid #000; }
            .pr-table thead th { background: none; border-bottom: 2px solid #000; }
            .pr-table tr, .pr-table td, .pr-table th { break-inside: avoid; }
            .pr-table .pr-amount { white-space: normal; }
            .pr-h i { display: none; }
        }
    </style>
@endif
@php
    $d = $block->data ?? [];
    $hid = 'pr-h-'.$block->id.'-'.\Illuminate\Support\Str::random(4);
    $prGroups = collect($d['rows'] ?? [])->filter(fn ($r) => filled($r['item'] ?? null))
        ->groupBy(fn ($r) => trim((string) ($r['group'] ?? '')));
@endphp
<section class="pr-frame not-prose" aria-labelledby="{{ $hid }}">
    <h2 id="{{ $hid }}" class="pr-h"><i class="fa-solid fa-coins" aria-hidden="true"></i> {{ $d['title'] ?? 'Cennik' }}</h2>
    @foreach ($prGroups as $gName => $items)
        @if ($gName !== '')<h3 class="pr-sub">{{ $gName }}</h3>@endif
        <div class="pr-table-wrap">
            <table class="pr-table" @if ($gName !== '') aria-label="{{ $gName }}" @else aria-label="{{ $d['title'] ?? 'Cennik' }}" @endif>
                <thead>
                    <tr>
                        <th scope="col">Pozycja</th>
                        <th scope="col">Opis</th>
                        <th scope="col">Cena</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $row)
                        <tr>
                            <th scope="row" class="pr-item">{{ $row['item'] }}</th>
                            <td class="pr-note">{{ $row['note'] ?? '' }}</td>
                            <td class="pr-amount">{{ $row['price'] ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
</section>
