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
    <summary>Co to znaczy, że działanie jest płatne? Jak to u nas działa?</summary>
    <div class="proj-paid-body">
        @foreach ($paidParagraphs as $para)
            <p>{!! nl2br(e($para)) !!}</p>
        @endforeach
        @if ($infoPage = $siteSettings->paidInfoPage())
            <p><a href="{{ route('page.show', $infoPage) }}" class="proj-paid-link">Więcej informacji o odpłatnej działalności <span aria-hidden="true">→</span><span class="sr-only">: {{ $infoPage->title }}</span></a></p>
        @endif
    </div>
</details>
@endif
