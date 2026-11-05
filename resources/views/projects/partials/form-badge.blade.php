{{-- Forma udziału na listach działań zwykłym tekstem (bez plakietki): „Odpłatne" (działanie płatne lub usługa odpłatna) albo „Bezpłatne".
     Dziedziczy kolor tekstu z wiersza/kafla (działa na białym i kolorowym tle). Zmienna: $project. --}}
@if ($project->isFoundationActivity())
    <span class="mt-1 inline-flex items-center gap-1.5 text-sm font-bold"><i class="fa-solid fa-newspaper text-xs" aria-hidden="true"></i>Relacja Fundacji</span>
@else
@php $isPaid = $project->is_paid || $project->isPaidOffer(); @endphp
<span class="mt-1 inline-flex items-center gap-1.5 text-sm font-bold"><i class="fa-solid {{ $isPaid ? 'fa-coins' : 'fa-gift' }} text-xs" aria-hidden="true"></i>{{ $isPaid ? 'Odpłatne' : 'Bezpłatne' }}</span>
@endif
