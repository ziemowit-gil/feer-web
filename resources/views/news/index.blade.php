@extends('layouts.site')

@section('title', 'Aktualności — ' . $siteSettings->site_name)

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Aktualności', 'url' => null],
    ]])
@endsection

@section('content')
    <section class="mx-auto max-w-6xl px-4 py-12">
        <h1 class="mb-8 text-3xl font-bold text-ink">Aktualności</h1>

        <div class="grid gap-8 md:grid-cols-3">
            @forelse ($news as $item)
                @php $ngoAccent = $item->accent_color ?: (($item->audience ?? 'brand') === 'ngo' ? $siteSettings->audienceColor('ngo') : null); @endphp
                <a href="{{ route('news.show', $item) }}"
                    @class([
                        'group block',
                        'rounded-xl border-2 border-amber-400 bg-amber-50/50 p-3' => $item->is_featured,
                        'rounded-xl border-2 p-3' => $ngoAccent && ! $item->is_featured,
                    ])
                    @if ($ngoAccent && ! $item->is_featured) style="border-color: {{ $ngoAccent }};" @endif>
                    @php $img = $item->imageUrlOrDefault(); @endphp
                    @if ($img)
                        <div class="relative mb-3 h-44 overflow-hidden rounded-lg bg-gray-100">
                            <img src="{{ $img }}" alt="" class="h-full w-full object-cover transition group-hover:scale-105">
                            @if ($item->category)
                                <span class="absolute left-3 top-3 rounded px-2 py-1 text-xs font-bold uppercase text-white" style="background-color: {{ $item->category->badgeColor() }}">{{ $item->category->name }}</span>
                            @endif
                        </div>
                    @endif
                    @if ($item->is_featured)
                        <span class="mb-1 inline-flex items-center gap-1 rounded-full bg-amber-400/20 px-2 py-0.5 text-xs font-bold text-amber-700">
                            <i class="fa-solid fa-star" aria-hidden="true"></i> Wyróżnione
                        </span>
                    @endif
                    <div class="text-xs font-medium text-muted">{{ $item->published_at->format('d.m.Y') }}</div>
                    <h3 class="mt-1 font-bold text-ink group-hover:text-brand">{{ $item->title }}</h3>
                    @if ($item->excerpt)
                        <p class="mt-1 text-sm text-muted">{{ $item->excerpt }}</p>
                    @endif
                </a>
            @empty
                <p class="text-muted">Brak opublikowanych newsów.</p>
            @endforelse
        </div>

        <div class="mt-10">
            {{ $news->links() }}
        </div>
    </section>
@endsection
