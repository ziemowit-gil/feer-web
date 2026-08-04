@extends('admin.layout')

@section('title', $facilitator->exists ? 'Edytuj prowadzącego/ą' : 'Nowy/a prowadzący/a')

@php
    $action = $facilitator->exists
        ? route('admin.prowadzacy.update', $facilitator)
        : route('admin.prowadzacy.store');
@endphp

@section('content')
    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="mt-4 max-w-2xl space-y-6"
        x-data="{ preview: @js($facilitator->photoUrl()) }">
        @csrf
        @if ($facilitator->exists) @method('PUT') @endif

        @if ($errors->any())
            <div class="rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <p class="font-bold">Popraw poniższe pola:</p>
                <ul class="mt-1 list-inside list-disc">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <div class="space-y-5 rounded-lg border border-gray-200 bg-white p-6">
            <div class="flex flex-wrap items-start gap-5">
                {{-- Zdjęcie --}}
                <div class="flex flex-col items-center gap-2">
                    <span class="h-24 w-24 overflow-hidden rounded-full bg-gray-100 ring-1 ring-gray-200">
                        <template x-if="preview">
                            <img :src="preview" alt="Podgląd zdjęcia" class="h-full w-full object-cover">
                        </template>
                        <template x-if="!preview">
                            <span class="flex h-full w-full items-center justify-center text-gray-300">
                                <i class="fa-solid fa-user text-3xl" aria-hidden="true"></i>
                            </span>
                        </template>
                    </span>
                    @if ($facilitator->photoUrl())
                        <label class="flex items-center gap-1.5 text-xs text-muted">
                            <input type="checkbox" name="remove_photo" value="1"
                                class="rounded border-gray-300 text-brand focus:ring-brand"
                                @change="if ($event.target.checked) preview = null">
                            Usuń zdjęcie
                        </label>
                    @endif
                </div>

                <div class="min-w-[16rem] flex-1 space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="name" class="mb-1 block text-sm font-bold">Imię i nazwisko <span aria-hidden="true" class="text-red-600">*</span></label>
                            <input type="text" id="name" name="name" value="{{ old('name', $facilitator->name) }}" required maxlength="160"
                                placeholder="np. Anna Kowalska"
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        </div>
                        <div>
                            <label for="role" class="mb-1 block text-sm font-bold">Rola / tytuł</label>
                            <input type="text" id="role" name="role" value="{{ old('role', $facilitator->role) }}" maxlength="160"
                                placeholder="np. trenerka dostępności cyfrowej"
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        </div>
                    </div>
                    <div>
                        <label for="photo" class="mb-1 block text-sm font-bold">Zdjęcie</label>
                        <input type="file" id="photo" name="photo" accept="image/*"
                            @change="const f = $event.target.files[0]; if (f) preview = URL.createObjectURL(f)"
                            class="w-full text-sm text-muted file:mr-3 file:rounded file:border-0 file:bg-brand file:px-3 file:py-2 file:text-sm file:font-bold file:text-white hover:file:bg-brand-dark">
                        <p class="mt-1 text-xs text-muted">Kwadratowe zdjęcie wygląda najlepiej (kadrowane do koła). Maks. 4 MB.</p>
                    </div>
                </div>
            </div>

            <div>
                <label for="bio" class="mb-1 block text-sm font-bold">Bio</label>
                <textarea id="bio" name="bio" rows="4" maxlength="2000"
                    placeholder="Kilka zdań o doświadczeniu osoby prowadzącej."
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('bio', $facilitator->bio) }}</textarea>
            </div>
        </div>

        <div class="space-y-3 rounded-lg border border-gray-200 bg-white p-6">
            <p class="text-sm font-bold">Linki i social media <span class="font-normal text-muted">(opcjonalnie)</span></p>
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="flex items-center gap-2">
                    <span class="w-5 flex-none text-center text-muted"><i class="fa-solid fa-globe" aria-hidden="true"></i></span>
                    <div class="flex-1">
                        <label for="website" class="mb-0.5 block text-xs font-bold">Strona WWW</label>
                        <input type="url" id="website" name="website" value="{{ old('website', $facilitator->website) }}" maxlength="500" placeholder="https://..."
                            class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-5 flex-none text-center" style="color:#0a66c2"><i class="fa-brands fa-linkedin" aria-hidden="true"></i></span>
                    <div class="flex-1">
                        <label for="linkedin" class="mb-0.5 block text-xs font-bold">LinkedIn</label>
                        <input type="url" id="linkedin" name="linkedin" value="{{ old('linkedin', $facilitator->linkedin) }}" maxlength="500" placeholder="https://linkedin.com/in/..."
                            class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-5 flex-none text-center" style="color:#1877f2"><i class="fa-brands fa-facebook" aria-hidden="true"></i></span>
                    <div class="flex-1">
                        <label for="facebook" class="mb-0.5 block text-xs font-bold">Facebook</label>
                        <input type="url" id="facebook" name="facebook" value="{{ old('facebook', $facilitator->facebook) }}" maxlength="500" placeholder="https://facebook.com/..."
                            class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-5 flex-none text-center" style="color:#e1306c"><i class="fa-brands fa-instagram" aria-hidden="true"></i></span>
                    <div class="flex-1">
                        <label for="instagram" class="mb-0.5 block text-xs font-bold">Instagram</label>
                        <input type="url" id="instagram" name="instagram" value="{{ old('instagram', $facilitator->instagram) }}" maxlength="500" placeholder="https://instagram.com/..."
                            class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-5 flex-none text-center text-ink"><i class="fa-brands fa-x-twitter" aria-hidden="true"></i></span>
                    <div class="flex-1">
                        <label for="twitter" class="mb-0.5 block text-xs font-bold">X / Twitter</label>
                        <input type="url" id="twitter" name="twitter" value="{{ old('twitter', $facilitator->twitter) }}" maxlength="500" placeholder="https://x.com/..."
                            class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">Zapisz</button>
            <a href="{{ route('admin.prowadzacy.index') }}" class="text-sm text-muted hover:text-ink">Anuluj</a>
        </div>
    </form>
@endsection
