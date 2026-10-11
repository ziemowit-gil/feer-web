@extends('admin.layout')

@section('title', 'Nowy podcast')

@section('content')
    <div class="mb-6 flex items-center gap-2 text-sm text-muted">
        <a href="{{ route('admin.podcasty.index') }}" class="hover:text-brand-dark">Podcasty</a>
        <i class="fa-solid fa-chevron-right text-xs" aria-hidden="true"></i>
        <span class="text-ink">Nowy odcinek</span>
    </div>

    <form method="POST" action="{{ route('admin.podcasty.store') }}" enctype="multipart/form-data" class="space-y-5">
        @csrf

        @include('admin.podcasts._form')

        <div class="flex items-center gap-3 px-6 py-4 rounded-md bg-white">
            <button type="submit"
                class="rounded-lg bg-brand-dark px-5 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                <i class="fa-solid fa-floppy-disk mr-1" aria-hidden="true"></i> Zapisz podcast
            </button>
            <a href="{{ route('admin.podcasty.index') }}"
                class="rounded-md px-5 py-2 text-sm font-medium text-muted bg-gray-100 hover:bg-brand-light">
                Anuluj
            </a>
        </div>
    </form>
@endsection
