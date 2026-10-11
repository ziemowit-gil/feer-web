@extends('admin.layout')

@section('title', 'Edytuj kategorię — Wiem FEER')

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.kategorie-bloga.index') }}" class="text-sm font-bold text-muted hover:text-brand">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Wróć do kategorii
        </a>
    </div>
    <div class="max-w-xl">
        @include('blog::admin.categories.fields', ['action' => route('admin.kategorie-bloga.update', $category), 'method' => 'PUT'])
    </div>
@endsection
