@extends('layouts.site')

@section('title', 'Panel organizacji — ' . $siteSettings->site_name)

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Organizacje członkowskie', 'url' => route('federation.organizations')],
        ['label' => 'Panel organizacji', 'url' => null],
    ]])
@endsection

@section('content')
    <section class="mx-auto max-w-2xl px-4 py-12 lg:py-16">
        <div class="mb-6 flex items-start justify-between gap-4">
            <div>
                <p class="text-sm font-extrabold uppercase tracking-widest text-brand">Panel organizacji</p>
                <h1 class="mt-1 text-2xl font-extrabold leading-tight tracking-tight text-ink sm:text-3xl">{{ $organization->name }}</h1>
            </div>
            <form method="POST" action="{{ route('organization.logout') }}">
                @csrf
                <button type="submit" class="text-sm font-semibold text-muted hover:text-brand">
                    <i class="fa-solid fa-right-from-bracket mr-1" aria-hidden="true"></i> Wyloguj
                </button>
            </form>
        </div>

        @if (session('status'))
            <div role="status" class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-sm font-semibold text-green-800">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('organization.panel.update') }}" enctype="multipart/form-data"
            class="space-y-5 rounded-lg border border-gray-100 bg-white p-6 shadow-sm">
            @csrf
            @method('PUT')

            <div>
                <label for="description" class="mb-1 block text-sm font-bold text-ink">Krótki opis (widoczny w katalogu)</label>
                <textarea id="description" name="description" rows="2"
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('description', $organization->description) }}</textarea>
                @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="bio" class="mb-1 block text-sm font-bold text-ink">Pełny opis (widoczny na wizytówce)</label>
                <textarea id="bio" name="bio" rows="5"
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('bio', $organization->bio) }}</textarea>
                @error('bio') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="website_url" class="mb-1 block text-sm font-bold text-ink">Strona internetowa</label>
                <input type="text" id="website_url" name="website_url" value="{{ old('website_url', $organization->website_url) }}" placeholder="https://…"
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                @error('website_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="facebook_url" class="mb-1 block text-sm font-bold text-ink"><i class="fa-brands fa-facebook mr-1 text-muted" aria-hidden="true"></i>Facebook</label>
                    <input type="text" id="facebook_url" name="facebook_url" value="{{ old('facebook_url', $organization->facebook_url) }}" placeholder="https://facebook.com/…"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    @error('facebook_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="instagram_url" class="mb-1 block text-sm font-bold text-ink"><i class="fa-brands fa-instagram mr-1 text-muted" aria-hidden="true"></i>Instagram</label>
                    <input type="text" id="instagram_url" name="instagram_url" value="{{ old('instagram_url', $organization->instagram_url) }}" placeholder="https://instagram.com/…"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    @error('instagram_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="email" class="mb-1 block text-sm font-bold text-ink">E-mail kontaktowy</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $organization->email) }}"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="phone" class="mb-1 block text-sm font-bold text-ink">Telefon kontaktowy</label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone', $organization->phone) }}"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            @php $photos = $organization->getMedia('photos'); @endphp
            <div class="border-t border-gray-100 pt-5">
                <label for="photos" class="mb-1 block text-sm font-bold text-ink">
                    Zdjęcia z działalności ({{ $photos->count() }}/{{ \App\Models\Organization::MAX_PHOTOS }})
                </label>
                <p class="mb-2 text-xs text-muted">Format JPG, PNG lub WebP, maks. 5 MB na zdjęcie.</p>
                @if ($photos->count() < \App\Models\Organization::MAX_PHOTOS)
                    <input type="file" id="photos" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple
                        class="block w-full cursor-pointer text-sm text-muted file:mr-3 file:cursor-pointer file:rounded file:border-0 file:bg-brand file:px-4 file:py-2 file:text-sm file:font-bold file:text-white hover:file:bg-brand-dark">
                @endif
                @error('photos') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                @error('photos.*') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

                @if ($photos->isNotEmpty())
                    <ul class="mt-4 grid grid-cols-3 gap-3 sm:grid-cols-4" role="list">
                        @foreach ($photos as $photo)
                            <li class="group relative overflow-hidden rounded-lg border border-gray-200">
                                <img src="{{ $photo->getAvailableUrl(['thumb']) }}" alt="" class="h-24 w-full object-cover">
                                <button type="submit" form="delete-photo-{{ $photo->id }}"
                                    class="absolute right-1 top-1 flex h-6 w-6 items-center justify-center rounded-full bg-black/60 text-white opacity-0 transition group-hover:opacity-100 focus-visible:opacity-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white"
                                    aria-label="Usuń zdjęcie {{ $loop->iteration }}">
                                    <i class="fa-solid fa-xmark text-xs" aria-hidden="true"></i>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="flex items-center gap-3 border-t border-gray-100 pt-5">
                <button type="submit" class="rounded-md bg-brand px-5 py-2.5 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">Zapisz zmiany</button>
                <a href="{{ route('federation.organizations.show', $organization) }}" class="text-sm font-semibold text-muted hover:text-brand">Zobacz wizytówkę</a>
            </div>
        </form>

        {{-- Osobne formularze usuwania zdjęć (przyciski w galerii wskazują na nie przez atrybut `form`). --}}
        @foreach ($photos ?? [] as $photo)
            <form id="delete-photo-{{ $photo->id }}" method="POST" action="{{ route('organization.panel.photos.destroy', $photo->id) }}" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        @endforeach
    </section>
@endsection
