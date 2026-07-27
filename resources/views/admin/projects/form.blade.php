@extends('admin.layout')

@section('title', $project->exists ? 'Edytuj projekt' : 'Nowy projekt')

@section('content')
    <form method="POST" action="{{ $project->exists ? route('admin.projekty.update', $project) : route('admin.projekty.store') }}"
        enctype="multipart/form-data" class="space-y-6">
        @csrf
        @if ($project->exists) @method('PUT') @endif

        <div data-project-form-tabs>
            <div class="mb-6 flex flex-wrap gap-1 border-b border-gray-200" role="tablist">
                <button type="button" data-ftab-btn="podstawowe" role="tab" aria-selected="true"
                    class="-mb-px border-b-2 border-brand px-4 py-2 text-sm font-bold text-brand">
                    <i class="fa-solid fa-circle-info" aria-hidden="true"></i> Podstawowe
                </button>
                <button type="button" data-ftab-btn="tresc" role="tab" aria-selected="false"
                    class="-mb-px border-b-2 border-transparent px-4 py-2 text-sm font-bold text-muted hover:text-brand">
                    <i class="fa-solid fa-align-left" aria-hidden="true"></i> Treść
                </button>
                <button type="button" data-ftab-btn="sekcje" role="tab" aria-selected="false"
                    class="-mb-px border-b-2 border-transparent px-4 py-2 text-sm font-bold text-muted hover:text-brand">
                    <i class="fa-solid fa-layer-group" aria-hidden="true"></i> Sekcje
                </button>
                <button type="button" data-ftab-btn="dodatkowe" role="tab" aria-selected="false"
                    class="-mb-px border-b-2 border-transparent px-4 py-2 text-sm font-bold text-muted hover:text-brand">
                    <i class="fa-solid fa-address-card" aria-hidden="true"></i> Koordynator i archiwum
                </button>
            </div>

            {{-- ============================ PODSTAWOWE ============================ --}}
            <div data-ftab-panel="podstawowe" class="space-y-6">
                <div class="space-y-5 rounded-lg border border-gray-200 bg-white p-6">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="title" class="mb-1 block text-sm font-bold">Tytuł</label>
                            <input type="text" id="title" name="title" value="{{ old('title', $project->title) }}" required
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                            @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="slug" class="mb-1 block text-sm font-bold">Slug (adres URL)</label>
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-muted">/projekty/</span>
                                <input type="text" id="slug" name="slug" value="{{ old('slug', $project->slug) }}" placeholder="zostanie wygenerowany z tytułu"
                                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                            </div>
                            @error('slug') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="excerpt" class="mb-1 block text-sm font-bold">Krótki opis</label>
                        <input type="text" id="excerpt" name="excerpt" value="{{ old('excerpt', $project->excerpt) }}"
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @error('excerpt') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="for_whom" class="mb-1 block text-sm font-bold">Dla kogo</label>
                            <input type="text" id="for_whom" name="for_whom" value="{{ old('for_whom', $project->for_whom) }}" placeholder="np. Szkoły podstawowe i urzędy gminne"
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                            @error('for_whom') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="since" class="mb-1 block text-sm font-bold">Od kiedy</label>
                            <input type="text" id="since" name="since" value="{{ old('since', $project->since) }}" placeholder="np. Od 2023 roku"
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                            @error('since') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="audience" class="mb-1 block text-sm font-bold">Grupa docelowa (kolorystyka)</label>
                        <select id="audience" name="audience" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand sm:w-1/2">
                            @foreach ($siteSettings->audienceOptions() as $value => $label)
                                <option value="{{ $value }}" {{ old('audience', $project->audience ?? 'brand') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-muted">Zmienia kolorystykę strony projektu na kolor wybranej submarki (definiowane w Ustawienia → Kolory). Domyślnie używany jest kolor marki.</p>
                        @error('audience') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="accent_color_text" class="mb-1 block text-sm font-bold">Własny kolor akcentu <span class="font-normal text-muted">(opcjonalnie)</span></label>
                        <div class="flex flex-wrap items-center gap-3">
                            <input type="color" id="accent_color_picker" value="{{ old('accent_color', $project->accent_color ?: '#c31432') }}"
                                oninput="document.getElementById('accent_color_text').value = this.value"
                                class="h-10 w-16 rounded border-gray-300" aria-label="Wybierz własny kolor akcentu">
                            <input type="text" id="accent_color_text" name="accent_color" value="{{ old('accent_color', $project->accent_color) }}"
                                placeholder="np. #0d7d4d — puste = jak wyżej"
                                oninput="if (/^#[0-9a-fA-F]{6}$/.test(this.value)) document.getElementById('accent_color_picker').value = this.value"
                                class="w-48 rounded border-gray-300 font-mono text-sm focus:border-brand focus:ring-brand">
                        </div>
                        <p class="mt-1 text-xs text-muted">Nadpisuje kolorystykę tej strony dowolnym kolorem (ma pierwszeństwo przed grupą docelowaną powyżej). Zbyt jasny kolor zostanie przyciemniony przy zapisie (kontrast WCAG). Puste = kolor z grupy docelowej.</p>
                        @error('accent_color') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="space-y-5 rounded-lg border border-gray-200 bg-white p-6">
                    <p class="text-sm font-bold uppercase tracking-wide text-muted">Publikacja</p>
                    <div class="grid gap-5 sm:grid-cols-3">
                        <div>
                            <label for="category_id" class="mb-1 block text-sm font-bold">Kategoria</label>
                            <select id="category_id" name="category_id" required class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                                <option value="" disabled {{ old('category_id', $project->category_id) ? '' : 'selected' }}>Wybierz kategorię</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" {{ (int) old('category_id', $project->category_id) === $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="order" class="mb-1 block text-sm font-bold">Kolejność</label>
                            <input type="number" id="order" name="order" min="0" value="{{ old('order', $project->order) }}"
                                class="w-28 rounded border-gray-300 focus:border-brand focus:ring-brand">
                        </div>

                        <div class="flex flex-col justify-center gap-2">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="is_published" value="1" {{ old('is_published', $project->is_published ?? true) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-brand focus:ring-brand">
                                <span class="text-sm font-bold">Opublikowany</span>
                            </label>

                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="is_completed" value="1" {{ old('is_completed', $project->is_completed ?? false) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-brand focus:ring-brand">
                                <span class="text-sm font-bold">Projekt już zrealizowany</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="space-y-4 rounded-lg border border-gray-200 bg-white p-6"
                    x-data="{ paid: {{ old('is_paid', $project->is_paid ?? false) ? 'true' : 'false' }} }">
                    <label class="flex items-center gap-2">
                        <input type="hidden" name="is_paid" value="0">
                        <input type="checkbox" name="is_paid" value="1" x-model="paid"
                            class="rounded border-gray-300 text-brand focus:ring-brand">
                        <span class="text-sm font-bold">Projekt odpłatny — pokaż cennik na stronie projektu</span>
                    </label>

                    <div x-show="paid" x-cloak data-pricing>
                        <p class="mb-2 text-sm font-bold uppercase tracking-wide text-muted">Cennik</p>
                        <p class="mb-3 text-xs text-muted">Pozycja + cena (i opcjonalnie krótki opis). Puste wiersze są pomijane.</p>
                        @php $pricingRows = array_values((array) old('pricing', $project->pricing ?? [])); @endphp
                        <div data-pricing-rows class="space-y-2">
                            @foreach ($pricingRows as $i => $row)
                                <div data-pricing-row class="grid gap-2 sm:grid-cols-[2fr_1fr_2fr_auto]">
                                    <input type="text" name="pricing[{{ $i }}][item]" value="{{ $row['item'] ?? '' }}" placeholder="Pozycja / usługa" aria-label="Pozycja cennika {{ $i + 1 }}" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                    <input type="text" name="pricing[{{ $i }}][price]" value="{{ $row['price'] ?? '' }}" placeholder="Cena, np. 200 zł" aria-label="Cena {{ $i + 1 }}" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                    <input type="text" name="pricing[{{ $i }}][note]" value="{{ $row['note'] ?? '' }}" placeholder="Opis (opcjonalnie)" aria-label="Opis pozycji {{ $i + 1 }}" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                    <button type="button" data-pricing-remove class="rounded p-2 text-muted hover:bg-red-50 hover:text-red-600" aria-label="Usuń pozycję cennika"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" data-pricing-add class="mt-3 inline-flex items-center gap-2 rounded border border-brand px-3 py-1.5 text-sm font-bold text-brand hover:bg-brand-light"><i class="fa-solid fa-plus"></i> Dodaj pozycję</button>
                        <template data-pricing-template>
                            <div data-pricing-row class="grid gap-2 sm:grid-cols-[2fr_1fr_2fr_auto]">
                                <input type="text" name="pricing[__INDEX__][item]" placeholder="Pozycja / usługa" aria-label="Pozycja cennika" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                <input type="text" name="pricing[__INDEX__][price]" placeholder="Cena, np. 200 zł" aria-label="Cena" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                <input type="text" name="pricing[__INDEX__][note]" placeholder="Opis (opcjonalnie)" aria-label="Opis pozycji" class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                <button type="button" data-pricing-remove class="rounded p-2 text-muted hover:bg-red-50 hover:text-red-600" aria-label="Usuń pozycję cennika"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                            </div>
                        </template>
                    </div>

                    <script>
                        (function () {
                            var wrap = document.currentScript.closest('[data-pricing]') || document.querySelector('[data-pricing]');
                            if (!wrap) return;
                            var rows = wrap.querySelector('[data-pricing-rows]');
                            var tpl = wrap.querySelector('[data-pricing-template]');
                            var add = wrap.querySelector('[data-pricing-add]');
                            var n = rows.querySelectorAll('[data-pricing-row]').length;
                            if (add) add.addEventListener('click', function () {
                                var html = tpl.innerHTML.replace(/__INDEX__/g, String(n++));
                                var d = document.createElement('div'); d.innerHTML = html.trim();
                                rows.appendChild(d.firstElementChild);
                            });
                            wrap.addEventListener('click', function (e) {
                                var rm = e.target.closest('[data-pricing-remove]');
                                if (rm) { var r = rm.closest('[data-pricing-row]'); if (r) r.remove(); }
                            });
                        })();
                    </script>
                </div>

                <div class="space-y-5 rounded-lg border border-gray-200 bg-white p-6">
                    <p class="text-sm font-bold uppercase tracking-wide text-muted">Zdjęcie</p>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="space-y-3">
                            @if ($project->exists && $project->image_url)
                                <div>
                                    <p class="mb-1 text-sm font-bold">Obecne zdjęcie</p>
                                    <img src="{{ $project->image_url }}" alt="{{ $project->image_alt ?: $project->title }}" class="h-32 w-full rounded object-cover">
                                </div>
                            @endif

                            <div>
                                <label for="image" class="mb-1 block text-sm font-bold">{{ $project->exists ? 'Zmień zdjęcie' : 'Zdjęcie' }}</label>
                                <input type="file" id="image" name="image" accept="image/*"
                                    class="block w-full cursor-pointer text-sm text-muted file:mr-3 file:cursor-pointer file:rounded file:border-0 file:bg-brand file:px-4 file:py-2 file:text-sm file:font-bold file:text-white hover:file:bg-brand-dark">
                                @error('image') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label for="image_alt" class="mb-1 block text-sm font-bold">Opis alternatywny zdjęcia</label>
                            <input type="text" id="image_alt" name="image_alt" value="{{ old('image_alt', $project->image_alt) }}"
                                placeholder="np. Zespół podczas audytu dostępności strony internetowej"
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                            <p class="mt-1 text-xs text-muted">Opisz, co przedstawia zdjęcie — czytają to osoby korzystające z czytników ekranu.</p>
                            @error('image_alt') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============================ TREŚĆ ============================ --}}
            <div data-ftab-panel="tresc" class="hidden space-y-6">
                <div class="space-y-5 rounded-lg border border-gray-200 bg-white p-6">
                    <div>
                        <label class="mb-1 block text-sm font-bold">Opis projektu</label>
                        @include('admin.partials.editor', ['name' => 'content', 'value' => old('content', $project->content)])
                        @error('content') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="why" class="mb-1 block text-sm font-bold">Dlaczego to robimy</label>
                        <textarea id="why" name="why" rows="4" placeholder="Uzasadnienie, motywacja stojąca za projektem"
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('why', $project->why) }}</textarea>
                        @error('why') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-bold">Co udało się osiągnąć</label>
                        @include('admin.partials.editor', ['name' => 'outcomes', 'value' => old('outcomes', $project->outcomes)])
                        <p class="mt-1 text-xs text-muted">Rezultaty, materiały i efekty, które zostają po zakończeniu projektu (np. raporty, narzędzia, nagrania, linki). Jeśli wypełnisz, na stronie projektu pojawi się osobna sekcja „Co udało się osiągnąć”.</p>
                        @error('outcomes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- ============================ SEKCJE ============================ --}}
            <div data-ftab-panel="sekcje" class="hidden space-y-6">
                <div class="space-y-5 rounded-lg border border-gray-200 bg-white p-6">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-wide text-muted">Dodatkowe sekcje</p>
                        <p class="mt-1 text-xs text-muted">Możesz dodać maksymalnie 3 własne sekcje (tytuł + treść). Wypełnione sekcje pojawią się na stronie projektu; puste są pomijane.</p>
                    </div>

                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="sections_as_tabs" value="1" {{ old('sections_as_tabs', $project->sections_as_tabs ?? false) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-brand focus:ring-brand">
                        <span class="text-sm font-bold">Wyświetl te sekcje jako zakładki <span class="font-normal text-muted">(przełącznik u góry strony projektu)</span></span>
                    </label>

                    @for ($i = 1; $i <= 3; $i++)
                        @php $customSection = data_get($project->custom_sections, $i - 1, []); @endphp
                        <div class="space-y-3 border-t border-gray-100 pt-5">
                            <div>
                                <label for="custom_section_title_{{ $i }}" class="mb-1 block text-sm font-bold">Tytuł sekcji {{ $i }}</label>
                                <input type="text" id="custom_section_title_{{ $i }}" name="custom_section_title_{{ $i }}"
                                    value="{{ old('custom_section_title_'.$i, $customSection['title'] ?? '') }}"
                                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                                @error('custom_section_title_'.$i) <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-bold">Treść sekcji {{ $i }}</label>
                                @include('admin.partials.editor', ['name' => 'custom_section_content_'.$i, 'value' => old('custom_section_content_'.$i, $customSection['content'] ?? '')])
                                @error('custom_section_content_'.$i) <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="custom_section_featured_{{ $i }}" value="1" {{ old('custom_section_featured_'.$i, $customSection['featured'] ?? false) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-brand focus:ring-brand">
                                <span class="text-sm font-bold">Wyróżnij tę sekcję <span class="font-normal text-muted">(ramka, na samej górze strony projektu)</span></span>
                            </label>
                        </div>
                    @endfor
                </div>
            </div>

            {{-- ==================== KOORDYNATOR I ARCHIWUM ==================== --}}
            <div data-ftab-panel="dodatkowe" class="hidden space-y-6">
                <div class="space-y-5 rounded-lg border border-gray-200 bg-white p-6">
                    <p class="text-sm font-bold uppercase tracking-wide text-muted">Koordynator projektu</p>
                    <p class="-mt-3 text-xs text-muted">Widoczny jako kontakt do projektu na jego stronie. Jeśli nie podasz e-maila koordynatora, wyświetli się ogólny e-mail kontaktowy fundacji.</p>

                    <div class="grid gap-5 sm:grid-cols-3">
                        <div>
                            <label for="coordinator_name" class="mb-1 block text-sm font-bold">Imię i nazwisko <span class="font-normal text-muted">(opcjonalnie)</span></label>
                            <input type="text" id="coordinator_name" name="coordinator_name" value="{{ old('coordinator_name', $project->coordinator_name) }}"
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                            @error('coordinator_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="coordinator_email" class="mb-1 block text-sm font-bold">E-mail <span class="font-normal text-muted">(opcjonalnie)</span></label>
                            <input type="email" id="coordinator_email" name="coordinator_email" value="{{ old('coordinator_email', $project->coordinator_email) }}"
                                placeholder="zostanie użyty ogólny e-mail fundacji"
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                            @error('coordinator_email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="coordinator_phone" class="mb-1 block text-sm font-bold">Telefon <span class="font-normal text-muted">(opcjonalnie)</span></label>
                            <input type="text" id="coordinator_phone" name="coordinator_phone" value="{{ old('coordinator_phone', $project->coordinator_phone) }}"
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                            @error('coordinator_phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <label class="flex items-start gap-2 border-t border-gray-100 pt-4">
                        <input type="hidden" name="show_coordinator" value="0">
                        <input type="checkbox" name="show_coordinator" value="1" {{ old('show_coordinator', $project->show_coordinator ?? true) ? 'checked' : '' }}
                            class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
                        <span class="text-sm font-bold">Pokazuj koordynatora
                            <span class="block font-normal text-muted">Gdy wyłączone, dane koordynatora nie pojawią się na stronie projektu ani na stronie „Kontakt”. Można też wyłączyć globalnie w Ustawienia → Kontakt.</span>
                        </span>
                    </label>

                    <label class="flex items-start gap-2 border-t border-gray-100 pt-4">
                        <input type="checkbox" name="is_featured_contact" value="1" {{ old('is_featured_contact', $project->is_featured_contact ?? false) ? 'checked' : '' }}
                            class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
                        <span class="text-sm font-bold">Wyróżniony kontakt
                            <span class="block font-normal text-muted">Na stronie „Kontakt” ten koordynator zostanie wyróżniony innym tłem i pokazany jako pierwszy.</span>
                        </span>
                    </label>
                </div>

                <div class="space-y-4 rounded-lg border border-gray-200 bg-white p-6">
                    <p class="text-sm font-bold uppercase tracking-wide text-muted">Archiwum</p>
                    <label class="flex items-start gap-2">
                        <input type="checkbox" name="show_legacy_box" value="1" {{ old('show_legacy_box', $project->show_legacy_box ?? false) ? 'checked' : '' }}
                            class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
                        <span class="text-sm font-bold">Pokaż informację, że działanie realizowaliśmy przed uruchomieniem nowej strony</span>
                    </label>
                    <div>
                        <label for="legacy_url" class="mb-1 block text-sm font-bold">Link do informacji o projekcie <span class="font-normal text-muted">(opcjonalnie)</span></label>
                        <input type="url" id="legacy_url" name="legacy_url" value="{{ old('legacy_url', $project->legacy_url) }}" placeholder="https://..."
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        <p class="mt-1 text-xs text-muted">Jeśli podasz link, w boxie pojawi się odnośnik „Zobacz informacje o projekcie”.</p>
                        @error('legacy_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark">Zapisz</button>
            <a href="{{ route('admin.projekty.index') }}" class="text-sm text-muted hover:text-brand">Anuluj</a>
        </div>
    </form>

    <script>
        (function () {
            const wrap = document.querySelector('[data-project-form-tabs]');
            if (!wrap) return;
            const buttons = Array.prototype.slice.call(wrap.querySelectorAll('[data-ftab-btn]'));
            const panels = Array.prototype.slice.call(wrap.querySelectorAll('[data-ftab-panel]'));

            function activate(key) {
                buttons.forEach(function (b) {
                    const active = b.dataset.ftabBtn === key;
                    b.classList.toggle('border-brand', active);
                    b.classList.toggle('text-brand', active);
                    b.classList.toggle('border-transparent', !active);
                    b.classList.toggle('text-muted', !active);
                    b.setAttribute('aria-selected', active ? 'true' : 'false');
                });
                panels.forEach(function (p) {
                    p.classList.toggle('hidden', p.dataset.ftabPanel !== key);
                });
                // A rich editor initialised inside a hidden panel can render blank;
                // toggling it re-lays it out once its tab becomes visible.
                const shown = panels.find(function (p) { return p.dataset.ftabPanel === key; });
                if (shown && window.tinymce) {
                    shown.querySelectorAll('textarea').forEach(function (ta) {
                        const ed = window.tinymce.get(ta.id);
                        if (ed) { ed.hide(); ed.show(); }
                    });
                }
                window.dispatchEvent(new Event('resize'));
            }

            buttons.forEach(function (btn) {
                btn.addEventListener('click', function () { activate(btn.dataset.ftabBtn); });
            });

            // Flag tabs that contain validation errors and jump to the first one.
            let firstErrorKey = null;
            panels.forEach(function (p) {
                if (!p.querySelector('.text-red-600')) return;
                const key = p.dataset.ftabPanel;
                const btn = buttons.find(function (b) { return b.dataset.ftabBtn === key; });
                if (btn && !btn.querySelector('[data-ftab-error]')) {
                    const dot = document.createElement('span');
                    dot.setAttribute('data-ftab-error', '');
                    dot.className = 'ml-1.5 inline-block h-2 w-2 rounded-full bg-red-500 align-middle';
                    btn.appendChild(dot);
                }
                if (!firstErrorKey) firstErrorKey = key;
            });
            if (firstErrorKey) activate(firstErrorKey);
        })();
    </script>
@endsection
