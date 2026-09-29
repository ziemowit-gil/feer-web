{{-- Blok powitalny szablonu "vm": nagłówek-pigułka, tekst i przyciski (edycja w panelu lub „na żywo"). --}}
@php
    $canInlineEdit = auth()->check() && auth()->user()->isAdmin();
    $vmButtons = $siteSettings->vmIntroButtons();
@endphp
<section class="py-14" aria-labelledby="vm-intro-heading"
    @if ($canInlineEdit) x-data="inlineContentEditor('site_setting', {{ $siteSettings->id }}, '{{ route('admin.inline-edit.update') }}')" @endif>
    @if ($canInlineEdit)
        @include('partials.inline-edit-bar')
    @endif

    <div class="mx-auto max-w-[860px] px-4 text-center">
        <h2 id="vm-intro-heading"
            class="vm-display mb-8 inline-block rounded-2xl bg-brand px-6 py-2.5 text-xl leading-tight text-white sm:text-2xl lg:text-3xl"
            @if ($canInlineEdit)
                :contenteditable="editMode ? 'true' : 'false'"
                @blur="if (editMode) saveField('vm_intro_heading', $el.innerText.trim())"
                :class="editMode ? 'outline-dashed outline-2 outline-offset-4 outline-brand' : ''"
            @endif>{{ $siteSettings->vmIntroHeading() }}</h2>

        <div class="space-y-4 font-semibold leading-relaxed text-ink [&_strong]:font-bold"
            @if ($canInlineEdit)
                :contenteditable="editMode ? 'true' : 'false'"
                @blur="if (editMode) saveField('vm_intro_text', $el.innerHTML.trim())"
                :class="editMode ? 'outline-dashed outline-2 outline-offset-4 outline-brand rounded' : ''"
            @endif>{!! $siteSettings->vmIntroText() !!}</div>

        @if ($vmButtons)
            <ul class="mt-10 flex flex-wrap justify-center gap-4 sm:gap-8" role="list">
                @foreach ($vmButtons as $button)
                    <li>
                        <a href="{{ $button['url'] }}"
                           class="vm-display inline-flex min-h-12 items-center gap-3 rounded-2xl bg-brand px-6 text-sm text-white shadow-md transition hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                            {{ $button['label'] }}
                            <span class="border-l border-white/60 pl-3" aria-hidden="true"><i class="fa-solid fa-angles-right"></i></span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</section>
