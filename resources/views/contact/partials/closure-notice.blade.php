{{--
    Moduł Kontakt: komunikat o zmianie godzin pracy lub nieobecności (Komunikacja → Kontakt). Pokazuje się tylko
    w podanych datach (bez dat: zawsze, dopóki ma treść) — bez zadań cyklicznych. Płaskie tło z akcentem po lewej
    jak „Ramka z komunikatem”; amber-950 na amber-50 ≥ 7:1; informację niesie ikona i tekst, nie sam kolor.
--}}
@php $closure = ($contactOptions ?? \Modules\Contact\Support\ContactOptions::for($siteSettings))->closure(); @endphp
@if ($closure)
    <div role="note" aria-label="Zmiana godzin pracy" class="contact-closure {{ $class ?? 'mb-6' }} flex items-start gap-4 rounded-lg border-l-4 border-amber-600 bg-amber-50 p-5 text-amber-950">
        <i class="fa-solid fa-calendar-xmark mt-1 shrink-0 text-xl text-amber-700" aria-hidden="true"></i>
        <div class="min-w-0">
            <p class="leading-relaxed">{!! nl2br(e($closure['text'])) !!}</p>
            @if ($closure['until'])
                <p class="mt-1 text-sm font-bold">Do {{ \Illuminate\Support\Carbon::parse($closure['until'])->locale('pl')->translatedFormat('j F Y') }}.</p>
            @endif
        </div>
    </div>
@endif
