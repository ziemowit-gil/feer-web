@extends('admin.layout')

@section('title', $category->portal_slug ? 'Portal: ' . ($category->portal_title ?: $category->name) : 'Nowy portal: ' . $category->name)

@section('content')
    <p class="mb-4"><a href="{{ route('admin.portale.index') }}" class="text-sm font-bold text-brand-dark underline underline-offset-2 hover:text-ink">← Wszystkie portale</a></p>

    <form method="POST" action="{{ route('admin.portale.update', $category) }}" class="max-w-3xl space-y-5 rounded-md bg-white p-6">
        @csrf
        @method('PUT')

        <p class="text-sm text-ink">Kategoria działań: <a href="{{ route('admin.kategorie.edit', $category) }}" class="font-bold text-brand-dark underline underline-offset-2 hover:text-ink">{{ $category->name }}</a>. Pozycje w menu portalu to działania z tej kategorii.</p>

        @include('admin.categories.partials.portal-fields')

        <div class="flex items-center gap-3 border-t border-gray-100 pt-5">
            <button type="submit" class="rounded bg-brand-dark px-5 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">Zapisz portal</button>
            <a href="{{ route('admin.portale.index') }}" class="text-sm text-muted hover:text-brand-dark">Anuluj</a>
        </div>
    </form>
@endsection
