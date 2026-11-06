{{-- Eksperci: prowadzący kursów (zdjęcie czarno-białe, imię, rola). --}}
@if ($experts->isNotEmpty())
<section class="el el-sec" aria-labelledby="el-exp-h">
    <div class="el-wrap">
        <h2 class="el-title" id="el-exp-h">Eksperci</h2>
        <ul class="el-people" role="list">
            @foreach ($experts as $person)
                <li class="el-person">
                    @if ($person->photoUrl())<img src="{{ $person->photoUrl() }}" alt="" loading="lazy">@else<span class="el-person-ph" aria-hidden="true"></span>@endif
                    <strong>{{ $person->name }}</strong>
                    @if (filled($person->role))<span>{{ $person->role }}</span>@endif
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
