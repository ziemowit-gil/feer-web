@php
    $canInlineEdit = auth()->check() && auth()->user()->isAdmin();
@endphp
<section class="bg-gray-50 py-14" aria-labelledby="wrzos-intro-heading"
    @if ($canInlineEdit) x-data="inlineContentEditor('site_setting', {{ $siteSettings->id }}, '{{ route('admin.inline-edit.update') }}')" @endif>
    @if ($canInlineEdit)
        @include('partials.inline-edit-bar')
    @endif

    <div class="mx-auto max-w-[900px] px-4 text-center">
        @if ($canInlineEdit)
            <h2 id="wrzos-intro-heading" :contenteditable="editMode ? 'true' : 'false'"
                @blur="if (editMode) saveField('wrzos_intro_heading', $el.innerText.trim())"
                :class="editMode ? 'outline-dashed outline-2 outline-offset-4 outline-brand rounded' : ''"
                class="mb-6 text-2xl font-extrabold tracking-tight text-ink sm:text-3xl">{{ $siteSettings->wrzosIntroHeading() }}</h2>
        @else
            <h2 id="wrzos-intro-heading" class="mb-6 text-2xl font-extrabold tracking-tight text-ink sm:text-3xl">
                {{ $siteSettings->wrzosIntroHeading() }}
            </h2>
        @endif

        @if ($canInlineEdit)
            <div :contenteditable="editMode ? 'true' : 'false'"
                @blur="if (editMode) saveField('wrzos_intro_text', $el.innerHTML.trim())"
                :class="editMode ? 'outline-dashed outline-2 outline-offset-4 outline-brand rounded' : ''"
                class="space-y-4 text-base leading-relaxed text-muted">{!! $siteSettings->wrzosIntroText() !!}</div>
        @else
            <div class="space-y-4 text-base leading-relaxed text-muted">{!! $siteSettings->wrzosIntroText() !!}</div>
        @endif

        @if ($siteSettings->isModuleEnabled('pages'))
            <a href="{{ url('/o-nas') }}"
                class="mt-5 inline-flex items-center gap-1.5 rounded-md bg-brand px-5 py-2.5 text-sm font-bold text-white transition hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                Więcej
                <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
            </a>
        @endif
    </div>
</section>
