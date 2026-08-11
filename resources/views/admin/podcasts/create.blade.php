@extends('admin.layout')

@section('title', 'Nowy podcast')

@section('content')
    <div class="mx-auto max-w-2xl">
        <h1 class="mb-6 text-xl font-bold text-ink">Nowy podcast</h1>

        <form method="POST" action="{{ route('admin.podcasty.store') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf

            @include('admin.podcasts._form')

            <div class="flex gap-3 pt-2">
                <button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-2 focus-visible:outline-brand">
                    Zapisz podcast
                </button>
                <a href="{{ route('admin.podcasty.index') }}" class="rounded border border-gray-300 px-5 py-2 text-sm font-medium text-muted hover:bg-gray-50">
                    Anuluj
                </a>
            </div>
        </form>
    </div>
@endsection
