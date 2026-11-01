{{--
    „Prowadzący”: do czterech osób z bazy prowadzących, które prowadzą widoczne szkolenia witryny (zdjęcie lub inicjał,
    imię i nazwisko, rola oraz do trzech ich szkoleń jako linki). Dane: $trainers (HomeController::portalExtras()).
--}}
@php $trainers ??= collect(); @endphp
@if ($trainers->isNotEmpty())
    <section class="fis-sec" aria-labelledby="fis-trainers-h">
        <div class="fis-wrap">
            <h2 id="fis-trainers-h">Kto prowadzi zajęcia</h2>
            <ul class="fis-trainers" role="list">
                @foreach ($trainers as $trainer)
                    <li>
                        @if ($trainer['photo'])
                            <img class="fis-trainer-photo" src="{{ $trainer['photo'] }}" alt="" loading="lazy">
                        @else
                            <span class="fis-trainer-photo fis-trainer-photo--none" aria-hidden="true">{{ mb_strtoupper(mb_substr($trainer['name'], 0, 1)) }}</span>
                        @endif
                        <h3>{{ $trainer['name'] }}</h3>
                        @if (filled($trainer['role']))<p class="fis-role">{{ $trainer['role'] }}</p>@endif
                        @if ($trainer['courses']->isNotEmpty())
                            <ul role="list" aria-label="Szkolenia: {{ $trainer['name'] }}">
                                @foreach ($trainer['courses'] as $course)<li><a href="{{ $course['url'] }}">{{ $course['title'] }}</a></li>@endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
@endif
