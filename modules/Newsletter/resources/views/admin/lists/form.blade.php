@extends('admin.layout')
@section('title', $list->exists ? 'Edycja listy' : 'Nowa lista')
@section('content')
    @include('newsletter::admin.partials.flash')
    <form method="POST" action="{{ $list->exists ? route('admin.newsletter.listy.update', $list) : route('admin.newsletter.listy.store') }}" class="max-w-xl space-y-4 rounded-lg border border-gray-200 bg-white p-6">
        @csrf @if ($list->exists) @method('PUT') @endif
        <div><label for="name" class="mb-1 block text-sm font-bold">Nazwa</label><input id="name" name="name" required value="{{ old('name', $list->name) }}" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand"></div>
        <div><label for="slug" class="mb-1 block text-sm font-bold">Slug</label><input id="slug" name="slug" value="{{ old('slug', $list->slug) }}" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand"><p class="mt-1 text-xs text-muted">Puste = z nazwy.</p></div>
        <div><label for="description" class="mb-1 block text-sm font-bold">Opis</label><textarea id="description" name="description" rows="3" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('description', $list->description) }}</textarea></div>
        <label class="flex items-center gap-2 text-sm"><input type="hidden" name="is_public" value="0"><input type="checkbox" name="is_public" value="1" class="rounded border-gray-300 text-brand focus:ring-brand" @checked(old('is_public', $list->is_public))> Publiczna — subskrybent może sam zapisać się/wypisać w preferencjach</label>
        <div class="flex gap-2 border-t border-gray-200 pt-4"><button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark">Zapisz</button><a href="{{ route('admin.newsletter.listy.index') }}" class="rounded border border-gray-300 bg-white px-5 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50">Anuluj</a></div>
    </form>
@endsection
