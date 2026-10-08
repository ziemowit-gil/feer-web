@extends('admin.layout')

@section('title', $navItem->exists ? 'Edytuj pozycję menu' : 'Nowa pozycja menu')

@section('content')
    {{--
        Zapasowa (bezmodalowa) strona formularza — działa również bez JavaScript.
        Panel administracyjny normalnie edytuje pozycje w dostępnym modalu na
        liście menu; ta strona pozostaje jako pełnoprawna alternatywa.
    --}}
    <form method="POST" action="{{ $navItem->exists ? route('admin.pozycje-menu.update', $navItem) : route('admin.pozycje-menu.store') }}" enctype="multipart/form-data"
        x-data="{ form: {
            editingId: {{ Js::from((string) ($navItem->id ?? '')) }},
            label: {{ Js::from(old('label', $navItem->label)) }},
            url: {{ Js::from(old('url', $navItem->url)) }},
            type: {{ Js::from(old('type', $navItem->type ?? 'link')) }},
            location: {{ Js::from(old('location', $navItem->location ?? request('location', 'main'))) }},
            parentId: {{ Js::from((string) old('parent_id', $navItem->parent_id ?? request('parent_id'))) }},
            module: {{ Js::from(old('module', $navItem->module ?? '')) }},
            isButton: {{ Js::from((bool) old('is_button', $navItem->is_button ?? false)) }},
            buttonColor: {{ Js::from(old('button_color', $navItem->button_color) ?: '#2563eb') }},
            buttonColorEnabled: {{ Js::from((bool) old('button_color', $navItem->button_color)) }},
            accentColor: {{ Js::from(old('accent_color', $navItem->accent_color) ?: '#1e6dff') }},
            accentEnabled: {{ Js::from((bool) old('accent_color', $navItem->accent_color)) }},
            isTransparent: {{ Js::from((bool) old('is_transparent_dropdown', $navItem->is_transparent_dropdown ?? false)) }},
            isMega: {{ Js::from((bool) old('is_mega', $navItem->is_mega ?? false)) }},
            isHeading: {{ Js::from((bool) old('is_column_heading', $navItem->is_column_heading ?? false)) }},
            megaImage: {{ Js::from(old('mega_image_url', $navItem->mega_image ?? '')) }},
            megaSize: {{ Js::from(old('mega_size', $navItem->mega_size ?? 'md')) }},
            megaExtraTitle: {{ Js::from(old('mega_extra_title', $navItem->mega_extra_title ?? '')) }},
            hideAllBtn: {{ Js::from((bool) old('hide_all_projects_btn', $navItem->hide_all_projects_btn ?? false)) }},
            hideArchiveBtn: {{ Js::from((bool) old('hide_archive_btn', $navItem->hide_archive_btn ?? false)) }},
            megaSideTitle: {{ Js::from(old('mega_side_title', $navItem->mega_side_title ?? '')) }},
            megaSideLinks: {{ Js::from(array_values((array) old('mega_side_links', $navItem->exists ? $navItem->megaSideLinks() : []))) }},
            megaImageAlt: {{ Js::from(old('mega_image_alt', $navItem->mega_image_alt ?? '')) }},
            icon: {{ Js::from(old('icon', $navItem->icon ?? '')) }},
            description: {{ Js::from(old('description', $navItem->description ?? '')) }},
            isActive: {{ Js::from((bool) old('is_active', $navItem->is_active ?? true)) }}
        } }"
        class="max-w-xl space-y-5 rounded-lg border border-gray-200 bg-white p-6">
        @csrf
        @if ($navItem->exists) @method('PUT') @endif

        @include('admin.nav-items._fields')

        <div class="flex items-center gap-3 border-t border-gray-100 pt-5">
            <button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">Zapisz</button>
            <a href="{{ route('admin.pozycje-menu.index', ['location' => old('location', $navItem->location ?? 'main')]) }}" class="rounded text-sm text-muted hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">Anuluj</a>
        </div>
    </form>
@endsection
