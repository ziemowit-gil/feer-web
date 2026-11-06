{{-- „Dla kogo?”: do trzech grup odbiorców zebranych z opisów kursów. --}}
@if ($audience->isNotEmpty())
<section class="el el-sec el-sec-white" aria-labelledby="el-aud-h">
    <div class="el-wrap">
        <h2 class="el-title" id="el-aud-h">Dla kogo?</h2>
        <ol class="el-aud" role="list">@foreach ($audience as $text)<li>{{ $text }}</li>@endforeach</ol>
    </div>
</section>
@endif
