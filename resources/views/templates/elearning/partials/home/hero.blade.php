{{-- Hero „elearning”: nagłówek (tagline lub nazwa witryny), przyciski do kursów i kontaktu, opis, zdjęcie z pierwszego slajdu. --}}
@include('templates.elearning.partials.base-css')
@php
    $elTitle = filled($siteSettings->tagline) ? $siteSettings->tagline : $siteSettings->site_name;
    $elHasCourses = $siteSettings->isModuleEnabled('courses') && \Illuminate\Support\Facades\Route::has('courses.index');
@endphp
<section class="el el-hero" aria-labelledby="el-hero-h">
    @if ($heroImage ?? null)<div class="el-hero-img" style="background-image:url('{{ $heroImage }}')" aria-hidden="true"></div>@endif
    <div class="el-wrap el-hero-in">
        <h1 id="el-hero-h">{{ $elTitle }}</h1>
        <div class="el-hero-cta">
            @if ($elHasCourses)<a class="el-btn el-btn-green" href="{{ site_route('courses.index') }}">Zobacz kursy</a>@endif
            @if (\Illuminate\Support\Facades\Route::has('contact.show'))<a class="el-btn el-btn-blue" href="{{ site_route('contact.show') }}">Kontakt</a>@endif
        </div>
        @if (filled($siteSettings->meta_description))<p class="el-lead">{{ $siteSettings->meta_description }}</p>@endif
    </div>
</section>
