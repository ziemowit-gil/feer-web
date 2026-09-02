@extends('admin.layout')

@section('title', $site->exists ? 'Edytuj witrynę' : 'Nowa sub-witryna')

@section('content')
    <form method="POST" action="{{ $site->exists ? route('admin.witryny.update', $site) : route('admin.witryny.store') }}"
        enctype="multipart/form-data" class="max-w-xl space-y-5 rounded-lg border border-gray-200 bg-white p-6">
        @csrf
        @if ($site->exists) @method('PUT') @endif

        @unless ($site->exists)
            <p class="rounded border border-brand/20 bg-brand-light px-3 py-2 text-sm text-ink">
                Nowa sub-witryna federacji „{{ \App\Models\SiteSetting::current()->site_name }}" — po zapisaniu
                przełącz się na nią (lista witryn), żeby uzupełnić resztę ustawień i dodać treść.
            </p>
        @endunless

        @if ($site->exists && $site->logoUrl())
            <div>
                <p class="mb-1 text-sm font-bold">Obecne logo</p>
                <img src="{{ $site->logoUrl() }}" alt="{{ $site->site_name }}" class="h-16 max-w-[12rem] rounded border border-gray-100 object-contain p-2">
            </div>
        @endif

        <div>
            <label for="logo" class="mb-1 block text-sm font-bold">{{ $site->exists ? 'Zmień logo' : 'Logo (opcjonalnie)' }}</label>
            <input type="file" id="logo" name="logo" accept="image/*"
                class="block w-full cursor-pointer text-sm text-muted file:mr-3 file:cursor-pointer file:rounded file:border-0 file:bg-brand file:px-4 file:py-2 file:text-sm file:font-bold file:text-white hover:file:bg-brand-dark">
            @error('logo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="site_name" class="mb-1 block text-sm font-bold">Nazwa witryny</label>
            <input type="text" id="site_name" name="site_name" value="{{ old('site_name', $site->site_name) }}" required
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            @error('site_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="tagline" class="mb-1 block text-sm font-bold">Hasło / podtytuł (opcjonalnie)</label>
            <input type="text" id="tagline" name="tagline" value="{{ old('tagline', $site->tagline) }}"
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
        </div>

        <div>
            <label for="brand_color" class="mb-1 block text-sm font-bold">Kolor marki (opcjonalnie)</label>
            <input type="text" id="brand_color" name="brand_color" value="{{ old('brand_color', $site->brand_color) }}" placeholder="#1d4ed8"
                class="w-40 rounded border-gray-300 font-mono focus:border-brand focus:ring-brand">
            <p class="mt-1 text-xs text-muted">Format hex, np. #1d4ed8. Puste = kolor marki głównej witryny.</p>
            @error('brand_color') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="slug" class="mb-1 block text-sm font-bold">Adres skrócony (slug)</label>
                <div class="flex items-center gap-1 text-sm text-muted">
                    <span class="shrink-0">{{ request()->getHttpHost() }}/</span>
                    <input type="text" id="slug" name="slug" value="{{ old('slug', $site->slug) }}" placeholder="osrodek"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                </div>
                <p class="mt-1 text-xs text-muted">Witryna będzie też osiągalna pod dłuższym adresem {{ request()->getHttpHost() }}/site/{ten-adres} — obie postacie działają, ta krótsza jest używana w generowanych linkach.</p>
                @error('slug') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="domain" class="mb-1 block text-sm font-bold">Własna domena (opcjonalnie)</label>
                <input type="text" id="domain" name="domain" value="{{ old('domain', $site->domain) }}" placeholder="np. przyklad.pl"
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                <p class="mt-1 text-xs text-muted">Domena musi wskazywać (DNS) na ten serwer.</p>
                @error('domain') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex items-center gap-3 border-t border-gray-100 pt-5">
            <button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">Zapisz</button>
            <a href="{{ route('admin.witryny.index') }}" class="text-sm text-muted hover:text-brand">Anuluj</a>
        </div>
    </form>
@endsection
