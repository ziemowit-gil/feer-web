@php $logos = ($partners ?? collect())->filter(fn ($p) => $p->logo_url)->values(); @endphp
@if ($logos->isNotEmpty())
<section class="el el-sec el-sec-white" aria-labelledby="el-par-h">
    <div class="el-wrap">
        <h2 class="el-title" id="el-par-h">Partnerzy</h2>
        <ul class="el-partners" role="list">
            @foreach ($logos as $partner)
                <li>
                    @if ($partner->url)
                        <a href="{{ $partner->url }}" target="_blank" rel="noopener"><img src="{{ $partner->logo_url }}" alt="{{ $partner->name }}" loading="lazy"><span class="sr-only"> (otwiera się w nowej karcie)</span></a>
                    @else
                        <img src="{{ $partner->logo_url }}" alt="{{ $partner->name }}" loading="lazy">
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
