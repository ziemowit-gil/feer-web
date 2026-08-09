@extends('admin.layout')

@section('title', $page->exists ? 'Edytuj osobę' : 'Nowa osoba')

@push('page_module_banner')
    <div class="flex items-center gap-3 border-b border-red-300 bg-red-600 px-6 py-2 text-sm font-semibold text-white"
         role="note" aria-label="Informacja o module">
        <i class="fa-solid fa-id-card-clip shrink-0" aria-hidden="true"></i>
        <span>Moduł dedykowany tylko dla FEER</span>
        @if ($page->exists && $page->parent)
            <a href="{{ route('admin.podstrony.edit', $page->parent) }}"
               class="ml-auto text-xs font-normal text-red-200 hover:text-white hover:underline">
                ← O organizacji: {{ $page->parent->title }}
            </a>
        @endif
    </div>
@endpush

@section('content')
    <nav class="mb-4 flex items-center gap-1.5 text-sm text-muted" aria-label="Breadcrumb">
        <a href="{{ route('admin.osoby.index') }}" class="text-brand hover:underline">
            <i class="fa-solid fa-users mr-1" aria-hidden="true"></i>Osoby
        </a>
        <span aria-hidden="true">/</span>
        <span class="text-ink">{{ $page->exists ? $page->title : 'Nowa osoba' }}</span>
    </nav>

    @if ($page->exists)
        @include('admin.partials.edit-lock', ['lockType' => 'page', 'lockId' => $page->id])
    @endif

    @php
        $socialValues = old('person_social', $page->person_social ?? []);
    @endphp

    <form method="POST"
          action="{{ $page->exists ? route('admin.podstrony.update', $page) : route('admin.podstrony.store') }}"
          enctype="multipart/form-data"
          class="space-y-6">
        @csrf
        @if ($page->exists) @method('PUT') @endif
        <input type="hidden" name="type" value="about_person">

        <div class="grid gap-6 lg:grid-cols-[1fr_280px]">

            {{-- ==== KOLUMNA GŁÓWNA ==== --}}
            <div class="space-y-6">

                {{-- Dane podstawowe --}}
                <div class="space-y-5 rounded-lg border border-gray-200 bg-white p-6">
                    <h2 class="text-base font-bold text-ink">Dane osoby</h2>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="title" class="mb-1 block text-sm font-bold">Imię i nazwisko <span class="text-red-500">*</span></label>
                            <input type="text" id="title" name="title"
                                value="{{ old('title', $page->title) }}"
                                required autocomplete="name"
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                            @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="person_name_genitive" class="mb-1 block text-sm font-bold">Imię i nazwisko (dopełniacz) <span class="font-normal text-muted">opcjonalnie</span></label>
                            <input type="text" id="person_name_genitive" name="person_name_genitive"
                                value="{{ old('person_name_genitive', $page->person_name_genitive) }}"
                                placeholder="np. Ziemowita Gila"
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                            @error('person_name_genitive') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="person_role" class="mb-1 block text-sm font-bold">Stanowisko / rola <span class="font-normal text-muted">opcjonalnie</span></label>
                        <input type="text" id="person_role" name="person_role"
                            value="{{ old('person_role', $page->person_role) }}"
                            placeholder="np. Koordynatorka projektów, wolontariusz…"
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @error('person_role') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="person_member_label" class="mb-1 block text-sm font-bold">Etykieta członkostwa <span class="font-normal text-muted">opcjonalnie</span></label>
                        <div class="mb-2 flex flex-wrap gap-1.5">
                            @foreach (['Członkini zespołu FEER', 'Członek zespołu FEER', 'Wolontariuszka', 'Wolontariusz', 'Współpracowniczka', 'Współpracownik'] as $lbl)
                                <button type="button"
                                    onclick="document.getElementById('person_member_label').value='{{ $lbl }}'"
                                    class="rounded border border-gray-300 px-2.5 py-1 text-xs hover:border-brand hover:text-brand">{{ $lbl }}</button>
                            @endforeach
                        </div>
                        <input type="text" id="person_member_label" name="person_member_label"
                            value="{{ old('person_member_label', $page->person_member_label) }}"
                            maxlength="60"
                            placeholder="np. Członkini zespołu FEER"
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @error('person_member_label') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Działy --}}
                    <div x-data="{
                        tags: @json(old('person_department', $page->person_department ?? [])),
                        input: '',
                        add() { const v = this.input.trim(); if (v && !this.tags.includes(v)) this.tags.push(v); this.input = ''; },
                        remove(t) { this.tags = this.tags.filter(x => x !== t); }
                    }">
                        <label class="mb-1 block text-sm font-bold">Działy / sekcje <span class="font-normal text-muted">opcjonalnie</span></label>
                        <div class="flex flex-wrap gap-1.5 rounded border border-gray-300 bg-white p-2 focus-within:border-brand focus-within:ring-1 focus-within:ring-brand">
                            <template x-for="t in tags" :key="t">
                                <span class="inline-flex items-center gap-1 rounded-full bg-brand-light px-2.5 py-0.5 text-xs font-bold text-brand-dark">
                                    <span x-text="t"></span>
                                    <button type="button" @click="remove(t)" :aria-label="'Usuń ' + t" class="text-brand hover:text-red-600">&times;</button>
                                    <input type="hidden" :name="'person_department[]'" :value="t">
                                </span>
                            </template>
                            <input type="text" x-model="input"
                                @keydown.enter.prevent="add()" @keydown.comma.prevent="add()"
                                placeholder="Wpisz dział i naciśnij Enter…"
                                class="min-w-[160px] flex-1 border-0 p-0 text-sm focus:ring-0">
                        </div>
                        <p class="mt-1 text-xs text-muted">np. Zarząd, Biuro — Enter lub przecinek dodaje dział</p>
                        @error('person_department') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Zdjęcie --}}
                <div class="space-y-4 rounded-lg border border-gray-200 bg-white p-6">
                    <h2 class="text-base font-bold text-ink">Zdjęcie profilowe</h2>

                    <div class="flex items-start gap-4">
                        @if (filled(old('content_image', $page->content_image ?? null)))
                            <img src="{{ old('content_image', $page->content_image) }}"
                                alt="Zdjęcie profilowe {{ $page->title }}"
                                class="h-24 w-24 shrink-0 rounded-full object-cover">
                            <label class="mt-2 flex items-center gap-1.5 text-sm text-red-600">
                                <input type="checkbox" name="remove_content_image" value="1"
                                    class="rounded border-gray-300 text-brand focus:ring-brand">
                                Usuń zdjęcie
                            </label>
                        @else
                            <span class="flex h-24 w-24 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-300" aria-hidden="true">
                                <i class="fa-solid fa-user text-4xl"></i>
                            </span>
                        @endif
                        <div class="min-w-0 flex-1 space-y-2">
                            <input type="file" name="content_image_file" accept="image/*"
                                aria-label="Wgraj zdjęcie profilowe"
                                class="block w-full text-sm text-muted file:mr-3 file:rounded file:border-0 file:bg-brand-light file:px-3 file:py-1.5 file:text-sm file:font-bold file:text-brand">
                            <input type="text" name="content_image"
                                value="{{ old('content_image', $page->content_image ?? '') }}"
                                placeholder="…albo wklej URL zdjęcia"
                                class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                            <div>
                                <label for="content_image_alt" class="mb-1 block text-xs font-bold">Tekst alternatywny (dostępność)</label>
                                <input type="text" id="content_image_alt" name="content_image_alt"
                                    value="{{ old('content_image_alt', $page->content_image_alt ?? '') }}"
                                    placeholder="np. Portret Anny Kowalskiej"
                                    class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Bio i kontakt --}}
                <div class="space-y-5 rounded-lg border border-gray-200 bg-white p-6">
                    <h2 class="text-base font-bold text-ink">Bio i kontakt</h2>

                    <div>
                        <label for="person_bio" class="mb-1 block text-sm font-bold">Krótkie o mnie <span class="font-normal text-muted">opcjonalnie</span></label>
                        <textarea id="person_bio" name="person_bio" rows="4"
                            placeholder="Kilka zdań — pojawi się wyróżnione na stronie osoby."
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('person_bio', $page->person_bio) }}</textarea>
                        @error('person_bio') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="person_phone" class="mb-1 block text-sm font-bold">Nr telefonu <span class="font-normal text-muted">opcjonalnie</span></label>
                            <input type="tel" id="person_phone" name="person_phone"
                                value="{{ old('person_phone', $page->person_phone) }}"
                                placeholder="+48 000 000 000"
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                            @error('person_phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="person_email" class="mb-1 block text-sm font-bold">E-mail <span class="font-normal text-muted">opcjonalnie</span></label>
                            <input type="email" id="person_email" name="person_email"
                                value="{{ old('person_email', $page->person_email) }}"
                                placeholder="osoba@feer.org.pl"
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                            @error('person_email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <p class="mb-3 text-sm font-bold">Social media <span class="font-normal text-muted">opcjonalnie</span></p>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="psf" class="mb-1 block text-xs font-bold text-muted">Facebook</label>
                                <input type="url" id="psf" name="person_social[facebook]"
                                    value="{{ $socialValues['facebook'] ?? '' }}"
                                    placeholder="https://facebook.com/…"
                                    class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                            </div>
                            <div>
                                <label for="psi" class="mb-1 block text-xs font-bold text-muted">Instagram</label>
                                <input type="url" id="psi" name="person_social[instagram]"
                                    value="{{ $socialValues['instagram'] ?? '' }}"
                                    placeholder="https://instagram.com/…"
                                    class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                            </div>
                            <div>
                                <label for="psl" class="mb-1 block text-xs font-bold text-muted">LinkedIn</label>
                                <input type="url" id="psl" name="person_social[linkedin]"
                                    value="{{ $socialValues['linkedin'] ?? '' }}"
                                    placeholder="https://linkedin.com/in/…"
                                    class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                            </div>
                            <div>
                                <label for="psw" class="mb-1 block text-xs font-bold text-muted">Strona www</label>
                                <input type="url" id="psw" name="person_social[website]"
                                    value="{{ $socialValues['website'] ?? '' }}"
                                    placeholder="https://…"
                                    class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SEO --}}
                <details class="rounded-lg border border-gray-200 bg-white">
                    <summary class="cursor-pointer px-6 py-4 text-sm font-bold text-ink hover:bg-gray-50">
                        <i class="fa-solid fa-magnifying-glass mr-1.5 text-muted" aria-hidden="true"></i>SEO
                    </summary>
                    <div class="border-t border-gray-100 px-6 py-5">
                        @include('admin.partials.seo-fields', ['model' => $page])
                    </div>
                </details>

                @if ($page->exists)
                    {{-- Galeria --}}
                    <details class="rounded-lg border border-gray-200 bg-white">
                        <summary class="cursor-pointer px-6 py-4 text-sm font-bold text-ink hover:bg-gray-50">
                            <i class="fa-solid fa-images mr-1.5 text-muted" aria-hidden="true"></i>Galeria zdjęć
                            @if ($page->images->isNotEmpty())
                                <span class="ml-1 rounded-full bg-gray-100 px-1.5 py-0.5 text-xs">{{ $page->images->count() }}</span>
                            @endif
                        </summary>
                        <div class="border-t border-gray-100 px-6 py-5">
                            @include('admin.partials.page-images')
                        </div>
                    </details>
                @endif

            </div>{{-- /kolumna główna --}}

            {{-- ==== SIDEBAR ==== --}}
            <div class="space-y-5">

                {{-- Zapis i anuluj --}}
                <div class="rounded-lg border border-gray-200 bg-white p-5">
                    <button type="submit"
                        class="w-full rounded bg-brand px-4 py-2.5 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                        <i class="fa-solid fa-floppy-disk mr-1.5" aria-hidden="true"></i>Zapisz
                    </button>
                    <a href="{{ route('admin.osoby.index') }}"
                        class="mt-2 block text-center text-sm text-muted hover:text-brand">Anuluj</a>

                    @if ($page->exists)
                        <div class="mt-3 border-t border-gray-100 pt-3 text-xs text-muted">
                            Slug: <code class="font-mono">/{{ $page->slug }}</code>
                        </div>
                    @endif
                </div>

                {{-- Publikacja --}}
                <div class="rounded-lg border border-gray-200 bg-white p-5"
                    x-data="{ pub: {{ old('is_published', $page->is_published ?? true) ? 'true' : 'false' }} }">
                    <p class="mb-3 text-sm font-bold text-ink">Publikacja</p>

                    <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-gray-200 p-3 hover:bg-gray-50">
                        <input type="checkbox" name="is_published" value="1"
                            {{ old('is_published', $page->is_published ?? true) ? 'checked' : '' }}
                            @change="pub = $event.target.checked"
                            class="rounded border-gray-300 text-brand focus:ring-brand">
                        <span class="text-sm font-bold">Opublikowana</span>
                    </label>

                    <div x-show="pub" x-cloak class="mt-3">
                        <label for="publish_at" class="mb-1 block text-xs font-bold text-muted">Opublikuj od</label>
                        <input type="datetime-local" id="publish_at" name="publish_at"
                            value="{{ old('publish_at', $page->publish_at?->format('Y-m-d\TH:i')) }}"
                            class="w-full rounded border-gray-300 text-xs focus:border-brand focus:ring-brand">
                        <p class="mt-1 text-xs text-muted">Puste = natychmiast.</p>
                    </div>
                </div>

                {{-- Strona nadrzędna --}}
                <div class="rounded-lg border border-gray-200 bg-white p-5">
                    <p class="mb-3 text-sm font-bold text-ink">Strona „O organizacji"</p>
                    <select name="parent_id"
                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand text-sm">
                        <option value="">— brak —</option>
                        @foreach ($parentOptions as $opt)
                            <option value="{{ $opt->id }}"
                                {{ (int) old('parent_id', $page->parent_id) === $opt->id ? 'selected' : '' }}>
                                {{ $opt->title }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-muted">Osoba pojawi się w sekcji Zespół tej strony.</p>
                    @error('parent_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

                    <div class="mt-4">
                        <label for="order" class="mb-1 block text-xs font-bold text-muted">Kolejność</label>
                        <input type="number" id="order" name="order" min="0"
                            value="{{ old('order', $page->order) }}"
                            class="w-28 rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                        <p class="mt-1 text-xs text-muted">Mniejsza = wyżej w liście.</p>
                    </div>
                </div>

                @if ($page->exists)
                    {{-- Historia --}}
                    <a href="{{ route('admin.historia.index', ['type' => 'page', 'id' => $page->id]) }}"
                        class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-3 text-sm text-muted hover:border-brand hover:text-brand">
                        <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>
                        Historia zmian
                    </a>
                @endif
            </div>{{-- /sidebar --}}

        </div>{{-- /grid --}}
    </form>
@endsection
