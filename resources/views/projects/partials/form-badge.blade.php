{{-- Znacznik formy udziału na listach projektów: „Odpłatne" (projekt płatny lub usługa odpłatna) albo „Bezpłatne".
     Dziedziczy kolor tekstu z kafla (działa na białym i kolorowym tle). Zmienna: $project. --}}
@php $isPaid = $project->is_paid || $project->isPaidOffer(); @endphp
@if (! request()->attributes->get('pf_css'))
    @php request()->attributes->set('pf_css', true); @endphp
    <style>
        .pf-badge { display: inline-flex; align-items: center; gap: .35rem; margin-top: .4rem; padding: .1rem .55rem; border: 2px solid currentColor; border-radius: .25rem; font-size: .72rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; line-height: 1.4; color: inherit; }
        .pf-badge i { font-size: .7rem; }
    </style>
@endif
<span class="pf-badge"><i class="fa-solid {{ $isPaid ? 'fa-coins' : 'fa-gift' }}" aria-hidden="true"></i>{{ $isPaid ? 'Odpłatne' : 'Bezpłatne' }}</span>
