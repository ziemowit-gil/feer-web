@extends('admin.layout')

@section('title', $group->exists ? 'Edytuj grupę' : 'Nowa grupa')

@section('content')
    <form method="POST" action="{{ $group->exists ? route('admin.grupy.update', $group) : route('admin.grupy.store') }}" class="max-w-xl space-y-5 rounded-lg border border-gray-200 bg-white p-6">
        @csrf
        @if ($group->exists) @method('PUT') @endif

        <div>
            <label for="name" class="mb-1 block text-sm font-bold">Nazwa grupy</label>
            <input type="text" id="name" name="name" value="{{ old('name', $group->name) }}" required
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <p class="mb-1 text-sm font-bold">Dostępne moduły</p>
            <p class="mb-3 text-xs text-muted">Użytkownicy przypisani do tej grupy (rola: edytor) będą mieli dostęp tylko do zaznaczonych modułów panelu.</p>

            @php $selectedModules = old('modules', $group->modules ?? []); @endphp

            <div class="grid gap-3 sm:grid-cols-2">
                @foreach (\App\Models\SiteSetting::MODULES as $key => $label)
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="modules[]" value="{{ $key }}" {{ in_array($key, $selectedModules) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-brand focus:ring-brand">
                        {{ $label }}
                    </label>
                @endforeach
            </div>
            @error('modules') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center gap-3 border-t border-gray-100 pt-5">
            <button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark">Zapisz</button>
            <a href="{{ route('admin.grupy.index') }}" class="text-sm text-muted hover:text-brand">Anuluj</a>
        </div>
    </form>
@endsection
