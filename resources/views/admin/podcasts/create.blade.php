@extends('admin.layout')

@section('title', 'Nowy podcast')

@section('content')
    <div class="mb-6 flex items-center gap-2 text-sm text-muted">
        <a href="{{ route('admin.podcasty.index') }}" class="hover:text-brand">Podcasty</a>
        <i class="fa-solid fa-chevron-right text-xs" aria-hidden="true"></i>
        <span class="text-ink">Nowy odcinek</span>
    </div>

    <form method="POST" action="{{ route('admin.podcasty.store') }}" enctype="multipart/form-data" class="space-y-5">
        @csrf

        @include('admin.podcasts._form')

        <div class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white px-6 py-4">
            <button type="submit"
                class="rounded-lg bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                <i class="fa-solid fa-floppy-disk mr-1" aria-hidden="true"></i> Zapisz podcast
            </button>
            <a href="{{ route('admin.podcasty.index') }}"
                class="rounded-lg border border-gray-300 px-5 py-2 text-sm font-medium text-muted hover:bg-gray-50">
                Anuluj
            </a>
        </div>
    </form>
@endsection
