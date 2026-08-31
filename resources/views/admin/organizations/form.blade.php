@extends('admin.layout')

@section('title', $organization->exists ? 'Edytuj organizację' : 'Nowa organizacja')

@section('content')
    <form method="POST" action="{{ $organization->exists ? route('admin.organizacje.update', $organization) : route('admin.organizacje.store') }}"
        class="max-w-xl space-y-5 rounded-lg border border-gray-200 bg-white p-6">
        @csrf
        @if ($organization->exists) @method('PUT') @endif

        <div>
            <label for="name" class="mb-1 block text-sm font-bold">Nazwa</label>
            <input type="text" id="name" name="name" value="{{ old('name', $organization->name) }}" required
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        @if ($organization->exists)
            <div>
                <label for="slug" class="mb-1 block text-sm font-bold">Slug (adres wizytówki)</label>
                <input type="text" id="slug" name="slug" value="{{ old('slug', $organization->slug) }}"
                    class="w-full rounded border-gray-300 font-mono text-sm focus:border-brand focus:ring-brand">
                @error('slug') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        @endif

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="town" class="mb-1 block text-sm font-bold">Miejscowość</label>
                <input type="text" id="town" name="town" value="{{ old('town', $organization->town) }}" required
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                @error('town') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="type" class="mb-1 block text-sm font-bold">Forma prawna</label>
                <select id="type" name="type" required class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    @foreach (\App\Models\Organization::TYPES as $t)
                        <option value="{{ $t }}" @selected(old('type', $organization->type) === $t)>{{ $t }}</option>
                    @endforeach
                </select>
                @error('type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div x-data="{
                available: {{ \Illuminate\Support\Js::from(array_keys(\App\Models\Organization::SPHERE_ICONS)) }},
                selected: {{ \Illuminate\Support\Js::from(old('spheres', $organization->spheres ?: [])) }},
                toAdd: '',
                add() { if (this.toAdd && ! this.selected.includes(this.toAdd)) { this.selected.push(this.toAdd); this.toAdd = '' } },
                remove(s) { this.selected = this.selected.filter(x => x !== s) },
             }">
            <label for="sphere-add" class="mb-1 block text-sm font-bold">Sfery pożytku publicznego (opcjonalnie)</label>
            <div class="flex gap-2">
                <select id="sphere-add" x-model="toAdd" class="flex-1 rounded border-gray-300 focus:border-brand focus:ring-brand">
                    <option value="">— wybierz sferę do dodania —</option>
                    <template x-for="s in available.filter(a => ! selected.includes(a))" :key="s">
                        <option :value="s" x-text="s"></option>
                    </template>
                </select>
                <button type="button" @click="add()"
                    class="flex-none rounded bg-brand px-3 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj
                </button>
            </div>

            <ul class="mt-3 space-y-2" role="list">
                <template x-for="s in selected" :key="s">
                    <li class="flex items-center justify-between rounded border border-gray-200 bg-gray-50/60 px-3 py-2 text-sm">
                        <span x-text="s"></span>
                        <div>
                            <input type="hidden" name="spheres[]" :value="s">
                            <button type="button" @click="remove(s)" class="text-muted hover:text-red-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand" :aria-label="'Usuń sferę: ' + s">
                                <i class="fa-solid fa-trash" aria-hidden="true"></i>
                            </button>
                        </div>
                    </li>
                </template>
            </ul>
            <p x-show="selected.length === 0" class="mt-2 text-xs text-muted">Nie wybrano żadnej sfery.</p>
            @error('spheres') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="description" class="mb-1 block text-sm font-bold">Krótki opis (do listy katalogowej)</label>
            <textarea id="description" name="description" rows="2"
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('description', $organization->description) }}</textarea>
            @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="bio" class="mb-1 block text-sm font-bold">Pełny opis (na wizytówce, opcjonalnie)</label>
            <textarea id="bio" name="bio" rows="5"
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('bio', $organization->bio) }}</textarea>
            @error('bio') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="website_url" class="mb-1 block text-sm font-bold">Strona internetowa (opcjonalnie)</label>
            <input type="text" id="website_url" name="website_url" value="{{ old('website_url', $organization->website_url) }}" placeholder="https://…"
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            @error('website_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="facebook_url" class="mb-1 block text-sm font-bold"><i class="fa-brands fa-facebook mr-1 text-muted" aria-hidden="true"></i>Facebook (opcjonalnie)</label>
                <input type="text" id="facebook_url" name="facebook_url" value="{{ old('facebook_url', $organization->facebook_url) }}" placeholder="https://facebook.com/…"
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                @error('facebook_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="instagram_url" class="mb-1 block text-sm font-bold"><i class="fa-brands fa-instagram mr-1 text-muted" aria-hidden="true"></i>Instagram (opcjonalnie)</label>
                <input type="text" id="instagram_url" name="instagram_url" value="{{ old('instagram_url', $organization->instagram_url) }}" placeholder="https://instagram.com/…"
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                @error('instagram_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="email" class="mb-1 block text-sm font-bold">E-mail (opcjonalnie)</label>
                <input type="email" id="email" name="email" value="{{ old('email', $organization->email) }}"
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="phone" class="mb-1 block text-sm font-bold">Telefon (opcjonalnie)</label>
                <input type="text" id="phone" name="phone" value="{{ old('phone', $organization->phone) }}"
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 border-t border-gray-100 pt-5">
            <div>
                <label for="login" class="mb-1 block text-sm font-bold">Login (do panelu organizacji)</label>
                <input type="text" id="login" name="login" value="{{ old('login', $organization->login) }}" required autocomplete="off"
                    class="w-full rounded border-gray-300 font-mono text-sm focus:border-brand focus:ring-brand">
                @error('login') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password" class="mb-1 block text-sm font-bold">
                    Hasło {{ $organization->exists ? '(zostaw puste, aby nie zmieniać)' : '' }}
                </label>
                <input type="password" id="password" name="password" autocomplete="new-password"
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="order" class="mb-1 block text-sm font-bold">Kolejność</label>
                <input type="number" id="order" name="order" min="0" value="{{ old('order', $organization->order) }}"
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            </div>
            <div class="flex items-end pb-2">
                <label class="flex items-center gap-2 text-sm font-bold">
                    <input type="checkbox" name="is_test" value="1" @checked(old('is_test', $organization->is_test))
                        class="rounded border-gray-300 text-brand focus:ring-brand">
                    Organizacja testowa (demo)
                </label>
            </div>
        </div>

        <div class="flex items-center gap-3 border-t border-gray-100 pt-5">
            <button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">Zapisz</button>
            <a href="{{ route('admin.organizacje.index') }}" class="text-sm text-muted hover:text-brand">Anuluj</a>
        </div>
    </form>
@endsection
