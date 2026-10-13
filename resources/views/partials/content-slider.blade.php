{{--
    Slider (Slidery) osadzony na stronie albo na blogu — ten sam, dostępny slajder co na stronie głównej
    (pauza, „ogranicz ruch”, zatrzymanie przy fokusie). Expects: $sliderId (id slidera) albo $slider.
--}}
@php
    $slider = $slider ?? (filled($sliderId ?? null) && \Illuminate\Support\Facades\Schema::hasTable('sliders')
        ? \App\Models\Slider::query()->forCurrentSite()->with('slides.media')->find((int) $sliderId)
        : null);
    $sliderSlides = $slider ? $slider->slides->filter(fn ($s) => $s->title || $s->image_url)->values() : collect();
@endphp
@if ($sliderSlides->isNotEmpty())
    <div class="not-prose mb-8 overflow-hidden rounded-md">
        @include('templates.feer.partials.hero', ['slides' => $sliderSlides, 'sliderLabel' => 'Slider: '.$slider->name, 'sliderDuration' => $slider->duration])
    </div>
@endif
