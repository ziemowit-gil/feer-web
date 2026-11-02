{{--
    Ramka z komunikatem (Ustawienia → Serwis): tylko na stronie głównej i w Kontakcie, gdy włączona i ma treść.
    Płaskie tło z akcentem po lewej; kontrast: ink na sky-50 i amber-950 na amber-50 ≥ 7:1. Informację niesie ikona,
    nagłówek i tekst, nie sam kolor; role="note" wskazuje ją czytnikom ekranu.
--}}
@php
    $noticePlace = request()->routeIs('home') ? 'home' : (request()->routeIs('contact.show', 'contact.store') ? 'contact' : null);
@endphp
@if ($noticePlace && $siteSettings->showsNoticeOn($noticePlace))
    @php
        $warn = $siteSettings->notice_style === 'warning';
        $noticeId = 'komunikat-'.$noticePlace;
    @endphp
    <div class="mx-auto max-w-6xl px-4 pt-6">
        <div role="note" @if (filled($siteSettings->notice_title)) aria-labelledby="{{ $noticeId }}" @endif
            class="site-notice flex items-start gap-4 rounded-lg border-l-4 p-5 {{ $warn ? 'border-amber-600 bg-amber-50 text-amber-950' : 'border-brand-dark bg-sky-50 text-ink' }}">
            <i class="fa-solid {{ $warn ? 'fa-triangle-exclamation text-amber-700' : 'fa-circle-info text-brand-dark' }} mt-1 shrink-0 text-xl" aria-hidden="true"></i>
            <div class="min-w-0">
                @if (filled($siteSettings->notice_title))
                    <p id="{{ $noticeId }}" class="text-lg font-bold">{{ $siteSettings->notice_title }}</p>
                @endif
                <p class="{{ filled($siteSettings->notice_title) ? 'mt-1 ' : '' }}leading-relaxed">{!! nl2br(e($siteSettings->notice_text)) !!}</p>
            </div>
        </div>
    </div>
@endif
