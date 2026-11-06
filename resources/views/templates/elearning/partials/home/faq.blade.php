@if ($faqs->isNotEmpty())
<section class="el el-sec" aria-labelledby="el-faq-h">
    <div class="el-wrap">
        <h2 class="el-title" id="el-faq-h">FAQ</h2>
        <div class="el-faq">
            @foreach ($faqs as $faq)
                <details><summary>{{ $faq->question }}</summary><div class="el-faq-a">{!! nl2br(e($faq->answer)) !!}</div></details>
            @endforeach
        </div>
    </div>
</section>
@endif
