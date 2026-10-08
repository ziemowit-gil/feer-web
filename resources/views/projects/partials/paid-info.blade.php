{{--
    Rozwijane objaśnienie odpłatnej działalności pożytku publicznego. Tekst: własny tekst projektu ($infoProject->paid_info_text),
    inaczej z Ustawień serwisu (SiteSetting::paidActivityInfo()). Natywne <details> — dostępne z klawiatury.
    Zmienne: $infoProject (Project, którego dotyczy objaśnienie).
--}}
@php
    $infoText = filled($infoProject->paid_info_text ?? null) ? $infoProject->paid_info_text : $siteSettings->paidActivityInfo();
    $paidParagraphs = array_values(array_filter(preg_split('/\\R{2,}/', trim($infoText))));
@endphp
@if ($infoProject->paid_info_show ?? true)
<details class="proj-paid-info mt-3">
    <summary>Czym jest odpłatna działalność pożytku publicznego i jak to u nas działa?</summary>
    <div class="proj-paid-body">
        @foreach ($paidParagraphs as $para)
            <p>{!! nl2br(e($para)) !!}</p>
        @endforeach
    </div>
</details>
@endif
