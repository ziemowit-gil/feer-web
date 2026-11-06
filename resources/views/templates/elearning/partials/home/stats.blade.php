@if (! empty($stats))
<section class="el el-sec" style="padding-top:0" aria-label="Platforma w liczbach">
    <ul class="el-stats" role="list">
        @foreach ($stats as $stat)<li><strong>{{ $stat['value'] }}</strong><span>{{ $stat['label'] }}</span></li>@endforeach
    </ul>
</section>
@endif
