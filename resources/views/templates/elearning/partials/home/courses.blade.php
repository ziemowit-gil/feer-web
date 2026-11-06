{{-- Kursy w kategoriach: po lewej nazwa kategorii i przycisk, po prawej przewijany rząd kart (cała karta jest linkiem). --}}
@if ($groups->isNotEmpty())
<section class="el el-sec" aria-labelledby="el-courses-h">
    <div class="el-wrap">
        <h2 class="el-title" id="el-courses-h">Kursy</h2>
        @foreach ($groups as $group)
            <div class="el-cat">
                <div>
                    <h3>{{ $group->label }}</h3>
                    <p>{{ trans_choice('{1} :count kurs|[2,4] :count kursy|[5,*] :count kursów', $group->courses->count()) }}</p>
                    <a class="el-btn el-btn-blue" href="{{ site_route('courses.index') }}">Wszystkie kursy<span class="sr-only"> (kategoria {{ $group->label }})</span></a>
                </div>
                <ul class="el-cards" role="list" aria-label="Kursy: {{ $group->label }}">
                    @foreach ($group->courses as $course)
                        <li class="el-card">
                            @if ($course->is_featured)<span class="el-new">Wyróżnione</span>@else<span class="el-card-top">{{ $group->label }}</span>@endif
                            <h4><a href="{{ site_route('courses.show', $course) }}">{{ $course->title }}</a></h4>
                            <ul class="el-card-meta" role="list">
                                <li>{{ $course->formatLabel() }}</li>
                                <li>{{ $course->priceLabel() }}</li>
                                @if ($course->facilitator)<li>{{ $course->facilitator->name }}</li>@endif
                            </ul>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
</section>
@endif
