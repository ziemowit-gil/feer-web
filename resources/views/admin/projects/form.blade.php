@extends('admin.layout')

@section('title', $project->exists ? 'Edytuj projekt' : 'Nowy projekt')

@section('content')
    @include('admin.partials.readable-form-css')
    @if ($project->exists)
        @include('admin.partials.edit-lock', ['lockType' => 'project', 'lockId' => $project->id])
    @endif

    <form method="POST" action="{{ $project->exists ? route('admin.projekty.update', $project) : route('admin.projekty.store') }}"
        enctype="multipart/form-data" class="space-y-6" data-readable>
        @csrf
        @if ($project->exists) @method('PUT') @endif

        <div data-project-form-tabs>
            <div class="mb-6 flex flex-wrap items-center gap-2" role="tablist">
                <button type="button" data-ftab-btn="podstawowe" role="tab" aria-selected="true"
                    class="rounded-md px-4 py-2 text-sm font-bold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 bg-brand text-white">
                    <i class="fa-solid fa-circle-info" aria-hidden="true"></i> Podstawowe
                </button>
                <button type="button" data-ftab-btn="tresc" role="tab" aria-selected="false"
                    class="rounded-md px-4 py-2 text-sm font-bold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 bg-gray-100 text-ink hover:bg-gray-200">
                    <i class="fa-solid fa-align-left" aria-hidden="true"></i> Treść
                </button>
                @if ($siteSettings->projects_extras_enabled)
                    <button type="button" data-ftab-btn="dodatki" role="tab" aria-selected="false" class="rounded-md px-4 py-2 text-sm font-bold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 bg-gray-100 text-ink hover:bg-gray-200">
                        <i class="fa-solid fa-circle-plus" aria-hidden="true"></i> Dodatkowe informacje
                    </button>
                @endif
                @if ($siteSettings->projects_terms_enabled)<button type="button" data-ftab-btn="warunki" role="tab" aria-selected="false" class="rounded-md px-4 py-2 text-sm font-bold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 bg-gray-100 text-ink hover:bg-gray-200">
                    <i class="fa-solid fa-user-check" aria-hidden="true"></i> Kto może wziąć udział
                </button>@endif
                <button type="button" data-ftab-btn="sekcje" role="tab" aria-selected="false"
                    class="rounded-md px-4 py-2 text-sm font-bold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 bg-gray-100 text-ink hover:bg-gray-200">
                    <i class="fa-solid fa-layer-group" aria-hidden="true"></i> Sekcje
                </button>
                @if ($siteSettings->projects_stages_enabled)
                    <button type="button" data-ftab-btn="etapy" role="tab" aria-selected="false" class="rounded-md px-4 py-2 text-sm font-bold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 bg-gray-100 text-ink hover:bg-gray-200">
                        <i class="fa-solid fa-timeline" aria-hidden="true"></i> Etapy i harmonogram
                    </button>
                @endif
                @if ($siteSettings->projects_team_funding_enabled)
                    <button type="button" data-ftab-btn="zespol" role="tab" aria-selected="false" class="rounded-md px-4 py-2 text-sm font-bold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 bg-gray-100 text-ink hover:bg-gray-200">
                        <i class="fa-solid fa-people-group" aria-hidden="true"></i> Zespół i finansowanie
                    </button>
                @endif
                <button type="button" data-ftab-btn="dodatkowe" role="tab" aria-selected="false"
                    class="rounded-md px-4 py-2 text-sm font-bold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 bg-gray-100 text-ink hover:bg-gray-200">
                    <i class="fa-solid fa-address-card" aria-hidden="true"></i> Koordynator i archiwum
                </button>
                <button type="button" data-ftab-btn="seo" role="tab" aria-selected="false"
                    class="rounded-md px-4 py-2 text-sm font-bold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 bg-gray-100 text-ink hover:bg-gray-200">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> SEO
                </button>
                @if ($project->exists)
                    <a href="{{ route('admin.historia.index', ['type' => 'project', 'id' => $project->id]) }}"
                        class="ml-auto rounded-md px-4 py-2 text-sm font-bold text-muted transition hover:bg-gray-100 hover:text-brand">
                        <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> Historia zmian
                    </a>
                @endif
            </div>

            {{-- ============================ PODSTAWOWE ============================ --}}
            <div data-ftab-panel="podstawowe" class="space-y-6">
                <div class="space-y-5 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="title" class="mb-1 block text-sm font-bold">Tytuł</label>
                            <input type="text" id="title" name="title" value="{{ old('title', $project->title) }}" required
                                class="w-full rounded-lg border-gray-300 focus:border-brand focus:ring-brand">
                            @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="slug" class="mb-1 block text-sm font-bold">Slug (adres URL)</label>
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-muted">/projekty/</span>
                                <input type="text" id="slug" name="slug" value="{{ old('slug', $project->slug) }}" placeholder="zostanie wygenerowany z tytułu"
                                    class="w-full rounded-lg border-gray-300 focus:border-brand focus:ring-brand">
                            </div>
                            @error('slug') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="excerpt" class="mb-1 block text-sm font-bold">Krótki opis</label>
                        <input type="text" id="excerpt" name="excerpt" value="{{ old('excerpt', $project->excerpt) }}"
                            class="w-full rounded-lg border-gray-300 focus:border-brand focus:ring-brand">
                        @error('excerpt') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="for_whom" class="mb-1 block text-sm font-bold">Dla kogo</label>
                            <input type="text" id="for_whom" name="for_whom" value="{{ old('for_whom', $project->for_whom) }}" placeholder="np. Szkoły podstawowe i urzędy gminne"
                                class="w-full rounded-lg border-gray-300 focus:border-brand focus:ring-brand">
                            @error('for_whom') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="since" class="mb-1 block text-sm font-bold">Od kiedy</label>
                            <input type="text" id="since" name="since" value="{{ old('since', $project->since) }}" placeholder="np. Od 2023 roku"
                                class="w-full rounded-lg border-gray-300 focus:border-brand focus:ring-brand">
                            @error('since') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="audience" class="mb-1 block text-sm font-bold">Grupa docelowa (kolorystyka)</label>
                            <select id="audience" name="audience" class="w-full rounded-lg border-gray-300 focus:border-brand focus:ring-brand">
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
                                    placeholder="np. #0d7d4d — puste = jak obok"
                                    oninput="if (/^#[0-9a-fA-F]{6}$/.test(this.value)) document.getElementById('accent_color_picker').value = this.value"
                                    class="w-48 rounded border-gray-300 font-mono text-sm focus:border-brand focus:ring-brand">
                            </div>
                            <p class="mt-1 text-xs text-muted">Nadpisuje kolorystykę tej strony dowolnym kolorem (ma pierwszeństwo przed grupą docelową obok). Zbyt jasny kolor zostanie przyciemniony przy zapisie (kontrast WCAG). Puste = kolor z grupy docelowej.</p>
                            @error('accent_color') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="space-y-5 rounded-lg border border-gray-200 bg-white p-6 shadow-sm"
                    x-data="{ parent: '{{ old('parent_id', $project->parent_id) }}', inh: @js(array_values((array) old('inherit', $project->inherit ?? []))) }">
                    <p class="text-sm font-bold uppercase tracking-wide text-muted">Publikacja</p>
                    <div>
                        <label for="kind" class="mb-1 block text-sm font-bold">Rodzaj</label>
                        <select id="kind" name="kind" class="w-full rounded-lg border-gray-300 focus:border-brand focus:ring-brand sm:w-2/3">
                            @foreach (\App\Models\Project::KINDS as $kk => $kl)
                                <option value="{{ $kk }}" {{ old('kind', $project->kind ?? 'project') === $kk ? 'selected' : '' }}>{{ $kl }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-muted">„Usługa wyłącznie odpłatna” zawsze jest odpłatna: pokazuje cennik, objaśnienie odpłatności i przycisk kontaktu zamiast opisu darmowego udziału.</p>
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="category_id" class="mb-1 block text-sm font-bold">Kategoria</label>
                            <select id="category_id" name="category_id" :required="! (parent && inh.includes('category'))" required class="w-full rounded-lg border-gray-300 focus:border-brand focus:ring-brand">
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
                                class="w-28 rounded-lg border-gray-300 focus:border-brand focus:ring-brand">
                        </div>
                    </div>

                    {{-- Podprojekt: np. „Szkolenia z obsługi komputera — płatne" i „— bezpłatne" pod jednym projektem nadrzędnym. --}}
                    <div class="space-y-3 rounded-lg border border-gray-100 bg-gray-50 p-4" @unless ($siteSettings->projects_subprojects_enabled || $project->parent_id) hidden @endunless>
                        <div>
                            <label for="parent_id" class="mb-1 block text-sm font-bold">Projekt nadrzędny <span class="font-normal text-muted">(opcjonalnie — tworzy podprojekt)</span></label>
                            <select id="parent_id" name="parent_id" x-model="parent" class="w-full rounded-lg border-gray-300 focus:border-brand focus:ring-brand sm:w-2/3">
                                <option value="">— to jest zwykły projekt —</option>
                                @foreach ($parentOptions as $po)
                                    <option value="{{ $po->id }}">{{ $po->title }}</option>
                                @endforeach
                            </select>
                            @error('parent_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            <p class="mt-1 text-xs text-muted">Podprojekt ma własną stronę, ale może częściowo dziedziczyć dane projektu nadrzędnego i wyświetla się na jego stronie.</p>
                        </div>
                        <div x-show="parent" x-cloak class="rounded-lg border border-gray-200 bg-white p-3">
                            <input type="hidden" name="is_offered_present" value="1">
                            <input type="hidden" name="is_offered" value="0">
                            <label class="flex items-start gap-3">
                                <input type="checkbox" name="is_offered" value="1" {{ old('is_offered', $project->is_offered ?? true) ? 'checked' : '' }} class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
                                <span>
                                    <span class="block text-sm font-bold">Aktualnie realizujemy tę formę udziału</span>
                                    <span class="block text-xs text-muted">Odznacz, jeśli ta forma udziału jest chwilowo niedostępna — na stronie działania nadrzędnego link dostanie oznaczenie „obecnie niedostępna”.</span>
                                </span>
                            </label>
                        </div>
                        <fieldset x-show="parent" x-cloak>
                            <legend class="mb-1 text-sm font-bold">Dziedzicz z projektu nadrzędnego</legend>
                            <p class="mb-2 text-xs text-muted">Zaznaczone pola są brane z projektu nadrzędnego i nie można ich zmienić na stronie podprojektu. Niezaznaczone ustawiasz tutaj osobno.</p>
                            <div class="grid gap-2 sm:grid-cols-2">
                                @foreach (\App\Models\Project::INHERITABLE as $ik => [$ilabel])
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="inherit[]" value="{{ $ik }}" x-model="inh" class="rounded border-gray-300 text-brand focus:ring-brand">
                                        {{ $ilabel }}
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    </div>

                    <div class="flex flex-wrap gap-x-6 gap-y-3 border-t border-gray-100 pt-4">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="is_published" value="1" {{ old('is_published', $project->is_published ?? true) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-brand focus:ring-brand">
                            <span class="text-sm font-bold">Opublikowany</span>
                        </label>

                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="is_completed" value="1" {{ old('is_completed', $project->is_completed ?? false) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-brand focus:ring-brand" data-completed-toggle>
                            <span class="text-sm font-bold">Projekt już zrealizowany</span>
                        </label>
                        <div class="mt-3 {{ old('is_completed', $project->is_completed ?? false) ? '' : 'hidden' }}" data-completed-fields>
                            <label for="completed_at" class="mb-1 block text-sm font-bold">Data zakończenia <span class="font-normal text-muted">(opcjonalnie)</span></label>
                            <input type="date" id="completed_at" name="completed_at" value="{{ old('completed_at', optional($project->completed_at ?? null)->format('Y-m-d')) }}"
                                class="w-full max-w-xs rounded-lg border-gray-300 focus:border-brand focus:ring-brand">
                            <p class="mt-1 text-xs text-muted">Pozwala filtrować archiwum „To już zrobiliśmy": <code>/projekty/archiwum?po=RRRR-MM-DD</code> pokaże projekty zakończone tego dnia lub później (analogicznie <code>?przed=</code>; projekt bez daty trafia do widoku „przed").</p>
                            @error('completed_at') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <script>
                            document.querySelector('[data-completed-toggle]')?.addEventListener('change', function () {
                                document.querySelector('[data-completed-fields]')?.classList.toggle('hidden', ! this.checked);
                            });
                        </script>
                    </div>
                </div>

                <div class="space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm"
                    x-data="{ paid: {{ old('is_paid', $project->is_paid ?? false) || old('kind', $project->kind ?? 'project') === 'paid_offer' ? 'true' : 'false' }} }" @change.window="if ($event.target && $event.target.id === 'kind' && $event.target.value === 'paid_offer') paid = true">
                    <label class="flex items-center gap-2">
                        <input type="hidden" name="is_paid" value="0">
                        <input type="checkbox" name="is_paid" value="1" x-model="paid"
                            class="rounded border-gray-300 text-brand focus:ring-brand">
                        <span class="text-sm font-bold">Projekt odpłatny — pokaż cennik na stronie projektu</span>
                    </label>

                    {{-- Objaśnienie odpłatnej działalności pożytku publicznego (tekst z Ustawień, z możliwością nadpisania dla projektu) --}}
                    <div x-show="paid" x-cloak class="space-y-3 rounded-lg border border-gray-100 bg-gray-50 p-4">
                        <label class="flex items-start gap-3">
                            <input type="hidden" name="paid_info_show" value="0">
                            <input type="checkbox" name="paid_info_show" value="1" {{ old('paid_info_show', $project->paid_info_show ?? true) ? 'checked' : '' }} class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
                            <span>
                                <span class="block text-sm font-bold">Pokaż objaśnienie odpłatnej działalności pożytku publicznego</span>
                                <span class="block text-xs text-muted">Rozwijane objaśnienie „Czym jest odpłatna działalność pożytku publicznego i jak to u nas działa?” przy odpłatnej wersji.</span>
                            </span>
                        </label>
                        <div>
                            <label for="paid_info_text" class="mb-1 block text-sm font-bold">Własny tekst objaśnienia <span class="font-normal text-muted">(puste = tekst z Ustawień → Treści)</span></label>
                            <textarea id="paid_info_text" name="paid_info_text" rows="5" maxlength="3000" placeholder="{{ \Illuminate\Support\Str::limit($siteSettings->paidActivityInfo(), 140) }}"
                                class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">{{ old('paid_info_text', $project->paid_info_text) }}</textarea>
                            <p class="mt-1 text-xs text-muted">Akapity rozdzielaj pustą linią.</p>
                            @error('paid_info_text') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

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
                        <button type="button" data-pricing-add class="mt-3 inline-flex items-center gap-2 rounded border border-brand px-3 py-1.5 text-sm font-bold text-brand hover:bg-brand-light"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj pozycję</button>
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

                <div class="space-y-5 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-bold uppercase tracking-wide text-muted">Zdjęcie</p>
                    <div class="grid gap-5 sm:grid-cols-2">
                        @if ($project->exists && $project->image_url)
                            <div class="sm:row-span-2">
                                <p class="mb-1 text-sm font-bold">Obecne zdjęcie</p>
                                <img src="{{ $project->image_url }}" alt="{{ $project->image_alt ?: $project->title }}" class="h-40 w-full rounded object-cover">
                            </div>
                        @endif

                        <div class="space-y-4">
                            <div>
                                <label for="image" class="mb-1 block text-sm font-bold">{{ $project->exists ? 'Zmień zdjęcie' : 'Zdjęcie' }}</label>
                                <input type="file" id="image" name="image" accept="image/*"
                                    class="block w-full cursor-pointer text-sm text-muted file:mr-3 file:cursor-pointer file:rounded file:border-0 file:bg-brand file:px-4 file:py-2 file:text-sm file:font-bold file:text-white hover:file:bg-brand-dark">
                                @error('image') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            @if (! ($project->exists && $project->image_url) && \App\Models\SiteSetting::current()->unsplashAccessKey())
                                {{-- Brak zdjęcia: sugestie z Unsplash dobrane do tytułu i kategorii, w ciepłym, ludzkim stylu FEER. --}}
                                <div x-data="unsplashSuggest('{{ route('admin.multimedia.unsplash.search') }}')" x-init="init()" class="rounded-lg border border-dashed border-gray-300 p-3">
                                    <p class="text-sm font-bold">Sugestie zdjęć z Unsplash</p>
                                    <p class="mb-2 text-xs text-muted">Brak zdjęcia — wybierz jedno z propozycji albo wgraj własny plik.</p>
                                    <p x-show="loading" class="text-xs text-muted" role="status">Szukam zdjęć…</p>
                                    <p x-show="error" x-cloak class="text-xs text-red-600" x-text="error" role="alert"></p>
                                    <p x-show="chosen" x-cloak class="mb-2 text-xs font-bold text-brand" role="status">Wybrano zdjęcie (autor: <span x-text="chosen?.author_name"></span>) — zostanie zapisane razem z projektem. <button type="button" @click="clear()" class="underline">Cofnij</button></p>
                                    <ul class="grid grid-cols-3 gap-2" role="list">
                                        <template x-for="p in photos" :key="p.id">
                                            <li>
                                                <button type="button" @click="pick(p)" :aria-pressed="(chosen?.id === p.id).toString()"
                                                    class="block w-full overflow-hidden rounded border-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"
                                                    :class="chosen?.id === p.id ? 'border-brand' : 'border-transparent'">
                                                    <img :src="p.thumb_url" :alt="p.alt" loading="lazy" class="h-20 w-full object-cover">
                                                </button>
                                            </li>
                                        </template>
                                    </ul>
                                    <button type="button" @click="load(photos.length > 0)" class="mt-2 text-xs font-bold text-brand hover:underline" x-text="photos.length ? 'Pokaż inne' : 'Szukaj sugestii'"></button>
                                    <input type="hidden" name="unsplash_full_url" :value="chosen?.full_url">
                                    <input type="hidden" name="unsplash_download_location" :value="chosen?.download_location">
                                    <input type="hidden" name="unsplash_author" :value="chosen?.author_name">
                                    <input type="hidden" name="unsplash_alt" :value="chosen?.alt">
                                </div>
                                <script>
                                    document.addEventListener('alpine:init', () => {
                                        Alpine.data('unsplashSuggest', (url) => ({
                                            photos: [], chosen: null, loading: false, error: '', page: 0,
                                            // Zapytanie: tytuł + kategoria + fraza stylu (ludzie, współpraca, naturalne światło).
                                            query() {
                                                const t = document.getElementById('title')?.value || '';
                                                const c = document.getElementById('category_id');
                                                const cat = c && c.selectedIndex > 0 ? c.options[c.selectedIndex].text : '';
                                                return (t + ' ' + cat).trim().slice(0, 60) + ' people together natural light';
                                            },
                                            init() { if (document.getElementById('title')?.value) this.load(false); },
                                            async load(more) {
                                                this.loading = true; this.error = '';
                                                try {
                                                    const r = await fetch(url + '?q=' + encodeURIComponent(this.query().slice(0, 100)), { headers: { Accept: 'application/json' } });
                                                    if (! r.ok) throw new Error();
                                                    const all = await r.json();
                                                    const size = 6;
                                                    this.page = more ? (this.page + 1) % Math.max(1, Math.ceil(all.length / size)) : 0;
                                                    this.photos = all.slice(this.page * size, this.page * size + size);
                                                    if (! this.photos.length) this.error = 'Brak propozycji — wgraj własne zdjęcie.';
                                                } catch (e) { this.error = 'Nie udało się pobrać sugestii z Unsplash.'; }
                                                this.loading = false;
                                            },
                                            pick(p) { this.chosen = p; },
                                            clear() { this.chosen = null; },
                                        }));
                                    });
                                </script>
                            @endif

                            <div>
                                <label for="image_alt" class="mb-1 block text-sm font-bold">Opis alternatywny zdjęcia</label>
                                <input type="text" id="image_alt" name="image_alt" value="{{ old('image_alt', $project->image_alt) }}"
                                    placeholder="np. Zespół podczas audytu dostępności strony internetowej"
                                    class="w-full rounded-lg border-gray-300 focus:border-brand focus:ring-brand">
                                <p class="mt-1 text-xs text-muted">Opisz, co przedstawia zdjęcie — czytają to osoby korzystające z czytników ekranu.</p>
                                @error('image_alt') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============================ TREŚĆ ============================ --}}
            <div data-ftab-panel="tresc" class="hidden space-y-6">
                <div class="space-y-5 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <div>
                        <label class="mb-1 block text-sm font-bold">Opis projektu</label>
                        @include('admin.partials.editor', ['name' => 'content', 'value' => old('content', $project->content)])
                        @error('content') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="why" class="mb-1 block text-sm font-bold">Dlaczego to robimy</label>
                        <textarea id="why" name="why" rows="4" placeholder="Uzasadnienie, motywacja stojąca za projektem"
                            class="w-full rounded-lg border-gray-300 focus:border-brand focus:ring-brand">{{ old('why', $project->why) }}</textarea>
                        @error('why') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-bold">Co udało się osiągnąć</label>
                        @include('admin.partials.editor', ['name' => 'outcomes', 'value' => old('outcomes', $project->outcomes)])
                        <p class="mt-1 text-xs text-muted">Rezultaty, materiały i efekty, które zostają po zakończeniu projektu (np. raporty, narzędzia, nagrania, linki). Jeśli wypełnisz, na stronie projektu pojawi się osobna sekcja „Co udało się osiągnąć".</p>
                        @error('outcomes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- ============================ WARUNKI UDZIAŁU ============================ --}}
            <div data-ftab-panel="warunki" class="hidden space-y-6"
                x-data="{ rows: @js(array_values((array) old('terms', $project->terms ?? []))), presets: ['Wiek', 'Miejsce', 'Wymagany sprzęt', 'Dostępność sali', 'Termin', 'Zapisy'],
                    move(i, d) { const j = i + d; if (j < 0 || j >= this.rows.length) return; const [x] = this.rows.splice(i, 1); this.rows.splice(j, 0, x); } }">
                <div class="space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-wide text-muted">Kto może wziąć udział</p>
                        <p class="mt-1 text-xs text-muted">Informacje wyświetlane na stronie w ramce „Kto może wziąć udział”. Pisz krótko i prostym językiem.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2" role="group" aria-label="Dodaj gotowy warunek">
                        <span class="text-xs font-bold text-muted">Dodaj:</span>
                        <template x-for="p in presets" :key="p">
                            <button type="button" @click="rows.push({ label: p, text: '' })" class="rounded-full border border-gray-300 bg-white px-3 py-1 text-xs font-bold text-ink hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand" x-text="p"></button>
                        </template>
                    </div>
                    <ul class="space-y-3" role="list">
                        <template x-for="(r, i) in rows" :key="i">
                            <li class="grid items-end gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4 sm:grid-cols-[1fr_2.5fr_auto]">
                                <div><label :for="'tm-l-' + i" class="mb-1 block text-xs font-bold text-muted">Nazwa warunku</label><input :id="'tm-l-' + i" type="text" :name="'terms[' + i + '][label]'" x-model="r.label" maxlength="80" class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand"></div>
                                <div><label :for="'tm-t-' + i" class="mb-1 block text-xs font-bold text-muted">Opis</label><input :id="'tm-t-' + i" type="text" :name="'terms[' + i + '][text]'" x-model="r.text" maxlength="300" placeholder="np. od 16 lat" class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand"></div>
                                <span class="flex gap-1">
                                    <button type="button" @click="move(i, -1)" :disabled="i === 0" class="rounded p-2 text-muted hover:bg-gray-100 disabled:opacity-40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand" aria-label="Przesuń wyżej"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i></button>
                                    <button type="button" @click="move(i, 1)" :disabled="i === rows.length - 1" class="rounded p-2 text-muted hover:bg-gray-100 disabled:opacity-40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand" aria-label="Przesuń niżej"><i class="fa-solid fa-arrow-down" aria-hidden="true"></i></button>
                                    <button type="button" @click="rows.splice(i, 1)" class="rounded p-2 text-red-700 hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500" aria-label="Usuń warunek"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                </span>
                            </li>
                        </template>
                    </ul>
                    <button type="button" @click="rows.push({ label: '', text: '' })" class="inline-flex min-h-10 items-center gap-2 rounded-lg border-2 border-dashed border-brand px-4 text-sm font-bold text-brand-dark hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj własny warunek</button>
                </div>
            </div>

            {{-- ============================ DODATKOWE INFORMACJE ============================ --}}
            @if ($siteSettings->projects_extras_enabled)
            @php $qf = (array) ($project->quick_facts ?? []); $mc = (array) ($project->main_cta ?? []); @endphp
            <div data-ftab-panel="dodatki" class="hidden space-y-6"
                x-data="{ metrics: @js(array_values((array) old('metrics', $project->metrics ?? []))), tests: @js(array_values((array) old('testimonials', $project->testimonials ?? []))), faq: @js(array_values((array) old('faq', $project->faq ?? []))),
                    move(list, i, d) { const j = i + d; if (j < 0 || j >= list.length) return; const [x] = list.splice(i, 1); list.splice(j, 0, x); } }">
                <div class="space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-bold uppercase tracking-wide text-muted">W skrócie</p>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label for="qf_duration" class="mb-1 block text-sm font-bold">Czas trwania</label><input type="text" id="qf_duration" name="quick_facts[duration]" value="{{ old('quick_facts.duration', $qf['duration'] ?? '') }}" maxlength="160" placeholder="np. 4 spotkania po 2 godziny" class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand"></div>
                        <div><label for="qf_place" class="mb-1 block text-sm font-bold">Miejsce</label><input type="text" id="qf_place" name="quick_facts[place]" value="{{ old('quick_facts.place', $qf['place'] ?? '') }}" maxlength="160" placeholder="np. Kraków, ul. Zamknięta 10" class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand"></div>
                        <div><label for="qf_mode" class="mb-1 block text-sm font-bold">Forma</label>
                            <select id="qf_mode" name="quick_facts[mode]" class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                <option value="">— nie pokazuj —</option>
                                @foreach (\App\Models\Project::MODES as $mk => $ml)<option value="{{ $mk }}" {{ old('quick_facts.mode', $qf['mode'] ?? '') === $mk ? 'selected' : '' }}>{{ $ml }}</option>@endforeach
                            </select></div>
                        <div><label for="qf_seats" class="mb-1 block text-sm font-bold">Liczba miejsc</label><input type="text" id="qf_seats" name="quick_facts[seats]" value="{{ old('quick_facts.seats', $qf['seats'] ?? '') }}" maxlength="80" placeholder="np. 12 osób" class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand"></div>
                    </div>
                </div>

                <div class="space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-bold uppercase tracking-wide text-muted">Prostym językiem i główny przycisk</p>
                    <div>
                        <label for="easy_summary" class="mb-1 block text-sm font-bold">Streszczenie prostym językiem (ETR)</label>
                        <textarea id="easy_summary" name="easy_summary" rows="4" maxlength="1200" placeholder="2–4 krótkie zdania: co to jest, dla kogo, co trzeba zrobić, żeby wziąć udział." class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">{{ old('easy_summary', $project->easy_summary) }}</textarea>
                        <p class="mt-1 text-xs text-muted">Pokazywane na początku strony w ramce „Prostym językiem”.</p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label for="cta_label" class="mb-1 block text-sm font-bold">Główny przycisk — etykieta</label><input type="text" id="cta_label" name="main_cta[label]" value="{{ old('main_cta.label', $mc['label'] ?? '') }}" maxlength="80" placeholder="np. Zapisz się" class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand"></div>
                        <div><label for="cta_url" class="mb-1 block text-sm font-bold">Adres przycisku</label><input type="text" id="cta_url" name="main_cta[url]" value="{{ old('main_cta.url', $mc['url'] ?? '') }}" placeholder="https://… albo /strona" class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
                            @error('main_cta.url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror</div>
                    </div>
                </div>

                <div class="space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-bold uppercase tracking-wide text-muted">Wskaźniki rezultatów</p>
                    <ul class="space-y-3" role="list">
                        <template x-for="(r, i) in metrics" :key="i">
                            <li class="grid items-end gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4 sm:grid-cols-[1fr_3fr_auto]">
                                <div><label :for="'me-v-' + i" class="mb-1 block text-xs font-bold text-muted">Liczba</label><input :id="'me-v-' + i" type="text" :name="'metrics[' + i + '][value]'" x-model="r.value" maxlength="40" placeholder="320" class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand"></div>
                                <div><label :for="'me-l-' + i" class="mb-1 block text-xs font-bold text-muted">Podpis</label><input :id="'me-l-' + i" type="text" :name="'metrics[' + i + '][label]'" x-model="r.label" maxlength="120" placeholder="osób przeszkolonych" class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand"></div>
                                <span class="flex gap-1">
                                    <button type="button" @click="move(metrics, i, -1)" :disabled="i === 0" class="rounded p-2 text-muted hover:bg-gray-100 disabled:opacity-40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand" aria-label="Przesuń wyżej"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i></button>
                                    <button type="button" @click="move(metrics, i, 1)" :disabled="i === metrics.length - 1" class="rounded p-2 text-muted hover:bg-gray-100 disabled:opacity-40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand" aria-label="Przesuń niżej"><i class="fa-solid fa-arrow-down" aria-hidden="true"></i></button>
                                    <button type="button" @click="metrics.splice(i, 1)" class="rounded p-2 text-red-700 hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                </span>
                            </li>
                        </template>
                    </ul>
                    <button type="button" @click="metrics.push({ value: '', label: '' })" class="inline-flex min-h-10 items-center gap-2 rounded-lg border-2 border-dashed border-brand px-4 text-sm font-bold text-brand-dark hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj wskaźnik</button>
                </div>

                <div class="space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-bold uppercase tracking-wide text-muted">Opinie uczestników</p>
                    <p class="text-xs text-muted">Dodawaj tylko opinie, na których publikację masz zgodę. Podpis może być samym imieniem lub inicjałami.</p>
                    <ul class="space-y-3" role="list">
                        <template x-for="(r, i) in tests" :key="i">
                            <li class="grid items-end gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4 sm:grid-cols-[3fr_1fr_auto]">
                                <div><label :for="'te-t-' + i" class="mb-1 block text-xs font-bold text-muted">Opinia</label><input :id="'te-t-' + i" type="text" :name="'testimonials[' + i + '][text]'" x-model="r.text" maxlength="600" class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand"></div>
                                <div><label :for="'te-a-' + i" class="mb-1 block text-xs font-bold text-muted">Podpis</label><input :id="'te-a-' + i" type="text" :name="'testimonials[' + i + '][author]'" x-model="r.author" maxlength="120" placeholder="np. Anna, uczestniczka" class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand"></div>
                                <span class="flex gap-1">
                                    <button type="button" @click="move(tests, i, -1)" :disabled="i === 0" class="rounded p-2 text-muted hover:bg-gray-100 disabled:opacity-40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand" aria-label="Przesuń wyżej"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i></button>
                                    <button type="button" @click="move(tests, i, 1)" :disabled="i === tests.length - 1" class="rounded p-2 text-muted hover:bg-gray-100 disabled:opacity-40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand" aria-label="Przesuń niżej"><i class="fa-solid fa-arrow-down" aria-hidden="true"></i></button>
                                    <button type="button" @click="tests.splice(i, 1)" class="rounded p-2 text-red-700 hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                </span>
                            </li>
                        </template>
                    </ul>
                    <button type="button" @click="tests.push({ text: '', author: '' })" class="inline-flex min-h-10 items-center gap-2 rounded-lg border-2 border-dashed border-brand px-4 text-sm font-bold text-brand-dark hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj opinię</button>
                </div>

                <div class="space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-bold uppercase tracking-wide text-muted">Pytania i odpowiedzi (FAQ)</p>
                    <ul class="space-y-3" role="list">
                        <template x-for="(r, i) in faq" :key="i">
                            <li class="space-y-3 rounded-lg border border-gray-200 bg-gray-50 p-4">
                                <div class="flex items-end gap-3">
                                    <div class="min-w-0 flex-1"><label :for="'fq-q-' + i" class="mb-1 block text-xs font-bold text-muted">Pytanie</label><input :id="'fq-q-' + i" type="text" :name="'faq[' + i + '][q]'" x-model="r.q" maxlength="300" class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand"></div>
                                    <span class="flex gap-1">
                                    <button type="button" @click="move(faq, i, -1)" :disabled="i === 0" class="rounded p-2 text-muted hover:bg-gray-100 disabled:opacity-40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand" aria-label="Przesuń wyżej"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i></button>
                                    <button type="button" @click="move(faq, i, 1)" :disabled="i === faq.length - 1" class="rounded p-2 text-muted hover:bg-gray-100 disabled:opacity-40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand" aria-label="Przesuń niżej"><i class="fa-solid fa-arrow-down" aria-hidden="true"></i></button>
                                    <button type="button" @click="faq.splice(i, 1)" class="rounded p-2 text-red-700 hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                </span>
                                </div>
                                <div><label :for="'fq-a-' + i" class="mb-1 block text-xs font-bold text-muted">Odpowiedź</label><textarea :id="'fq-a-' + i" :name="'faq[' + i + '][a]'" x-model="r.a" rows="3" maxlength="3000" class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand"></textarea></div>
                            </li>
                        </template>
                    </ul>
                    <button type="button" @click="faq.push({ q: '', a: '' })" class="inline-flex min-h-10 items-center gap-2 rounded-lg border-2 border-dashed border-brand px-4 text-sm font-bold text-brand-dark hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj pytanie</button>
                </div>
            </div>
            @endif

            {{-- ============================ SEKCJE ============================ --}}
            <div data-ftab-panel="sekcje" class="hidden space-y-6">
                {{-- Struktura podstron projektu: drzewo jak w Stronach, dowolna głębokość. Każda podstrona to pełnoprawna strona (typ, treść, SEO). --}}
                <div class="space-y-3 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-bold uppercase tracking-wide text-muted">Struktura podstron</p>
                            <p class="mt-1 text-xs text-muted">Podstrony projektu w drzewie — dowolnie zagnieżdżone. Na stronie projektu tworzą menu boczne; podstrony najwyższego poziomu mają tryb wyświetlania (zakładka, sekcja, odnośnik).</p>
                        </div>
                        @if ($project->exists)
                            <a href="{{ route('admin.podstrony.create', ['project_id' => $project->id, 'project_display' => 'tab']) }}"
                               class="inline-flex items-center gap-2 rounded-lg bg-brand px-3 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                                <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj podstronę
                            </a>
                        @endif
                    </div>
                    <div>
                        <label for="sections_nav" class="mb-1 block text-sm font-bold">Nawigacja po sekcjach</label>
                        <select id="sections_nav" name="sections_nav" class="w-full rounded-lg border-gray-300 focus:border-brand focus:ring-brand sm:w-2/3">
                            @php $secNav = old('sections_nav', $project->sections_nav); @endphp
                            <option value="" {{ $secNav ? '' : 'selected' }}>Jak w ustawieniach serwisu ({{ \App\Models\SiteSetting::current()->project_sections_nav === 'sidebar' ? 'menu boczne' : 'zakładki' }})</option>
                            <option value="tabs" {{ $secNav === 'tabs' ? 'selected' : '' }}>Zakładki</option>
                            <option value="sidebar" {{ $secNav === 'sidebar' ? 'selected' : '' }}>Menu boczne</option>
                        </select>
                    </div>
                    @if (! $project->exists)
                        <p class="text-sm text-muted">Zapisz projekt, aby dodawać podstrony.</p>
                    @else
                        @php $pageTree = $project->pageTree(); @endphp
                        @if ($pageTree->isEmpty())
                            <p class="text-sm text-muted">Projekt nie ma jeszcze podstron.</p>
                        @else
                            <p class="text-xs text-muted">Kliknięcie tytułu otwiera edycję podstrony — zapisz najpierw zmiany w projekcie.</p>
                            <ul class="space-y-1.5" role="list">
                                @foreach ($pageTree as $node)
                                    @include('admin.projects.partials.page-tree-node', ['node' => $node, 'project' => $project, 'depth' => 0])
                                @endforeach
                            </ul>
                        @endif
                    @endif
                    <div class="space-y-3 border-t border-gray-100 pt-4"
                         x-data="{ rows: @js(array_values((array) old('sidebar_buttons', $project->sidebar_buttons ?? []))) }">
                        <div>
                            <p class="text-sm font-bold">Elementy pod menu sekcji</p>
                            <p class="text-xs text-muted">Wyświetlane pod menu sekcji na stronie projektu (do 6 przycisków i krótka notka).</p>
                        </div>
                        <div>
                            <label for="sidebar_note" class="mb-1 block text-sm font-bold">Krótka notka <span class="font-normal text-muted">(opcjonalnie)</span></label>
                            <textarea id="sidebar_note" name="sidebar_note" rows="2" maxlength="1000" class="w-full rounded-lg border-gray-300 focus:border-brand focus:ring-brand">{{ old('sidebar_note', $project->sidebar_note) }}</textarea>
                        </div>
                        <template x-for="(row, i) in rows" :key="i">
                            <div class="grid items-end gap-2 rounded-lg border border-gray-200 p-3 sm:grid-cols-[1fr_1.4fr_auto]">
                                <div>
                                    <label :for="'sb_label_' + i" class="mb-1 block text-xs font-bold">Etykieta</label>
                                    <input type="text" :id="'sb_label_' + i" :name="'sidebar_buttons[' + i + '][label]'" x-model="row.label" maxlength="80" class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                </div>
                                <div>
                                    <label :for="'sb_url_' + i" class="mb-1 block text-xs font-bold">Adres (https://…, mailto:, tel:, /ścieżka)</label>
                                    <input type="text" :id="'sb_url_' + i" :name="'sidebar_buttons[' + i + '][url]'" x-model="row.url" class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                </div>
                                <button type="button" @click="rows.splice(i, 1)" class="rounded border border-gray-300 px-2 py-2 text-xs font-bold text-red-700 hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                                    <i class="fa-solid fa-trash" aria-hidden="true"></i><span class="sr-only">Usuń przycisk</span>
                                </button>
                                <div class="flex flex-wrap items-center gap-4 sm:col-span-3">
                                    <label class="flex items-center gap-2 text-sm"><span class="font-bold">Styl</span>
                                        <select :name="'sidebar_buttons[' + i + '][style]'" x-model="row.style" class="rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                            <option value="outline">Z obwódką (jak „Na skróty”)</option><option value="primary">Wypełniony (negatyw)</option>
                                        </select>
                                    </label>
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" :name="'sidebar_buttons[' + i + '][new_tab]'" value="1" x-model="row.new_tab" class="rounded border-gray-300 text-brand focus:ring-brand"> Otwórz w nowej karcie
                                    </label>
                                </div>
                            </div>
                        </template>
                        <button type="button" x-show="rows.length < 6" @click="rows.push({ label: '', url: '', style: 'outline', new_tab: false })"
                            class="inline-flex items-center gap-2 rounded border border-gray-300 px-3 py-1.5 text-sm font-bold text-ink hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                            <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj przycisk
                        </button>
                    </div>
                </div>
            </div>

            {{-- ==================== ETAPY I HARMONOGRAM ==================== --}}
            @if ($siteSettings->projects_stages_enabled)
            <div data-ftab-panel="etapy" class="hidden space-y-6"
                x-data="{ rows: @js(array_values((array) old('stages', $project->stages ?? []))), move(list, i, d) { const j = i + d; if (j < 0 || j >= list.length) return; const [x] = list.splice(i, 1); list.splice(j, 0, x); } }">
                <div class="space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-bold uppercase tracking-wide text-muted">Status i terminy</p>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label for="status" class="mb-1 block text-sm font-bold">Status realizacji</label>
                            <select id="status" name="status" class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                <option value="">— nie pokazuj —</option>
                                @foreach (\App\Models\Project::STATUSES as $sk => $sl)
                                    <option value="{{ $sk }}" {{ old('status', $project->status) === $sk ? 'selected' : '' }}>{{ $sl }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="starts_on" class="mb-1 block text-sm font-bold">Początek</label>
                            <input type="date" id="starts_on" name="starts_on" value="{{ old('starts_on', $project->starts_on?->format('Y-m-d')) }}" class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
                        </div>
                        <div>
                            <label for="ends_on" class="mb-1 block text-sm font-bold">Koniec</label>
                            <input type="date" id="ends_on" name="ends_on" value="{{ old('ends_on', $project->ends_on?->format('Y-m-d')) }}" class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
                            @error('ends_on') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <p class="text-xs text-muted">Status „Zakończony” oznacza też projekt jako zrealizowany (trafia do archiwum).</p>
                </div>
                <div class="space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-bold uppercase tracking-wide text-muted">Etapy (harmonogram)</p>
                    <ol class="space-y-3" role="list">
                        <template x-for="(r, i) in rows" :key="i">
                            <li class="space-y-3 rounded-lg border border-gray-200 bg-gray-50 p-4">
                                <div class="grid items-end gap-3 sm:grid-cols-[2fr_1fr_1fr_1fr_auto]">
                                    <div><label :for="'st-t-' + i" class="mb-1 block text-xs font-bold text-muted">Nazwa etapu</label><input :id="'st-t-' + i" type="text" :name="'stages[' + i + '][title]'" x-model="r.title" maxlength="160" class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand"></div>
                                    <div><label :for="'st-f-' + i" class="mb-1 block text-xs font-bold text-muted">Od</label><input :id="'st-f-' + i" type="date" :name="'stages[' + i + '][from]'" x-model="r.from" class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand"></div>
                                    <div><label :for="'st-e-' + i" class="mb-1 block text-xs font-bold text-muted">Do</label><input :id="'st-e-' + i" type="date" :name="'stages[' + i + '][to]'" x-model="r.to" class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand"></div>
                                    <div><label :for="'st-s-' + i" class="mb-1 block text-xs font-bold text-muted">Stan</label>
                                        <select :id="'st-s-' + i" :name="'stages[' + i + '][state]'" x-model="r.state" class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
                                            @foreach (\App\Models\Project::STAGE_STATES as $sk => $sl)<option value="{{ $sk }}">{{ $sl }}</option>@endforeach
                                        </select></div>
                                    <span class="flex gap-1">
                                        <button type="button" @click="move(rows, i, -1)" :disabled="i === 0" class="rounded p-2 text-muted hover:bg-gray-100 disabled:opacity-40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand" aria-label="Przesuń wyżej"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i></button>
                                        <button type="button" @click="move(rows, i, 1)" :disabled="i === rows.length - 1" class="rounded p-2 text-muted hover:bg-gray-100 disabled:opacity-40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand" aria-label="Przesuń niżej"><i class="fa-solid fa-arrow-down" aria-hidden="true"></i></button>
                                        <button type="button" @click="rows.splice(i, 1)" class="rounded p-2 text-red-700 hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                    </span>
                                </div>
                                <div><label :for="'st-x-' + i" class="mb-1 block text-xs font-bold text-muted">Opis etapu (opcjonalnie)</label><input :id="'st-x-' + i" type="text" :name="'stages[' + i + '][text]'" x-model="r.text" maxlength="600" class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand"></div>
                            </li>
                        </template>
                    </ol>
                    <button type="button" @click="rows.push({ title: '', from: '', to: '', state: 'upcoming', text: '' })" class="inline-flex min-h-10 items-center gap-2 rounded-lg border-2 border-dashed border-brand px-4 text-sm font-bold text-brand-dark hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj etap</button>
                </div>
            </div>
            @endif

            {{-- ==================== ZESPÓŁ, PARTNERZY, FINANSOWANIE ==================== --}}
            @if ($siteSettings->projects_team_funding_enabled)
            @php $allPartners = \App\Models\Partner::orderBy('order')->orderBy('name')->get(); $selPartners = array_map('intval', (array) old('partner_ids', $project->exists ? $project->partners->pluck('id')->all() : [])); $fund = (array) ($project->funding ?? []); @endphp
            <div data-ftab-panel="zespol" class="hidden space-y-6"
                x-data="{ rows: @js(array_values((array) old('team', $project->team ?? []))), srcs: @js(array_values((array) old('funding.sources', $fund['sources'] ?? []))), move(list, i, d) { const j = i + d; if (j < 0 || j >= list.length) return; const [x] = list.splice(i, 1); list.splice(j, 0, x); } }">
                <div class="space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-bold uppercase tracking-wide text-muted">Zespół projektu</p>
                    <ul class="space-y-3" role="list">
                        <template x-for="(r, i) in rows" :key="i">
                            <li class="grid items-end gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4 sm:grid-cols-[1fr_1fr_2fr_auto]">
                                <div><label :for="'tm-n-' + i" class="mb-1 block text-xs font-bold text-muted">Imię i nazwisko</label><input :id="'tm-n-' + i" type="text" :name="'team[' + i + '][name]'" x-model="r.name" maxlength="120" class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand"></div>
                                <div><label :for="'tm-r-' + i" class="mb-1 block text-xs font-bold text-muted">Rola</label><input :id="'tm-r-' + i" type="text" :name="'team[' + i + '][role]'" x-model="r.role" maxlength="160" class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand"></div>
                                <div><label :for="'tm-x-' + i" class="mb-1 block text-xs font-bold text-muted">Krótki opis (opcjonalnie)</label><input :id="'tm-x-' + i" type="text" :name="'team[' + i + '][text]'" x-model="r.text" maxlength="400" class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand"></div>
                                <span class="flex gap-1">
                                        <button type="button" @click="move(rows, i, -1)" :disabled="i === 0" class="rounded p-2 text-muted hover:bg-gray-100 disabled:opacity-40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand" aria-label="Przesuń wyżej"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i></button>
                                        <button type="button" @click="move(rows, i, 1)" :disabled="i === rows.length - 1" class="rounded p-2 text-muted hover:bg-gray-100 disabled:opacity-40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand" aria-label="Przesuń niżej"><i class="fa-solid fa-arrow-down" aria-hidden="true"></i></button>
                                        <button type="button" @click="rows.splice(i, 1)" class="rounded p-2 text-red-700 hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                    </span>
                            </li>
                        </template>
                    </ul>
                    <button type="button" @click="rows.push({ name: '', role: '', text: '' })" class="inline-flex min-h-10 items-center gap-2 rounded-lg border-2 border-dashed border-brand px-4 text-sm font-bold text-brand-dark hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj osobę</button>
                </div>

                <div class="space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-bold uppercase tracking-wide text-muted">Partnerzy</p>
                    @if ($allPartners->isEmpty())
                        <p class="text-sm text-muted">Brak partnerów — dodaj ich w module Partnerzy.</p>
                    @else
                        <div class="flex flex-wrap gap-2">
                            @foreach ($allPartners as $pt)
                                <label class="cursor-pointer">
                                    <input type="checkbox" name="partner_ids[]" value="{{ $pt->id }}" {{ in_array($pt->id, $selPartners, true) ? 'checked' : '' }} class="peer sr-only">
                                    <span class="inline-flex min-h-9 items-center rounded-full border border-gray-300 bg-white px-3 text-sm font-medium text-ink peer-checked:border-brand peer-checked:bg-brand peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-brand peer-focus-visible:ring-offset-2">{{ $pt->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-bold uppercase tracking-wide text-muted">Finansowanie</p>
                    <ul class="space-y-3" role="list">
                        <template x-for="(r, i) in srcs" :key="i">
                            <li class="grid items-end gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4 sm:grid-cols-[1.2fr_2fr_1.2fr_auto]">
                                <div><label :for="'fu-n-' + i" class="mb-1 block text-xs font-bold text-muted">Źródło / grantodawca</label><input :id="'fu-n-' + i" type="text" :name="'funding[sources][' + i + '][name]'" x-model="r.name" maxlength="160" class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand"></div>
                                <div><label :for="'fu-x-' + i" class="mb-1 block text-xs font-bold text-muted">Opis (np. program, kwota)</label><input :id="'fu-x-' + i" type="text" :name="'funding[sources][' + i + '][text]'" x-model="r.text" maxlength="400" class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand"></div>
                                <div><label :for="'fu-u-' + i" class="mb-1 block text-xs font-bold text-muted">Adres (opcjonalnie)</label><input :id="'fu-u-' + i" type="text" :name="'funding[sources][' + i + '][url]'" x-model="r.url" placeholder="https://…" class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand"></div>
                                <span class="flex gap-1"><button type="button" @click="move(srcs, i, -1)" :disabled="i === 0" class="rounded p-2 text-muted hover:bg-gray-100 disabled:opacity-40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand" aria-label="Przesuń wyżej"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i></button><button type="button" @click="srcs.splice(i, 1)" class="rounded p-2 text-red-700 hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500" aria-label="Usuń źródło"><i class="fa-solid fa-trash" aria-hidden="true"></i></button></span>
                            </li>
                        </template>
                    </ul>
                    <button type="button" @click="srcs.push({ name: '', text: '', url: '' })" class="inline-flex min-h-10 items-center gap-2 rounded-lg border-2 border-dashed border-brand px-4 text-sm font-bold text-brand-dark hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj źródło finansowania</button>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="funding_budget" class="mb-1 block text-sm font-bold">Budżet projektu</label>
                            <input type="text" id="funding_budget" name="funding[budget]" value="{{ old('funding.budget', $fund['budget'] ?? '') }}" maxlength="80" placeholder="np. 120 000 zł" class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
                        </div>
                        <label class="flex items-center gap-2 self-end pb-2 text-sm">
                            <input type="hidden" name="funding[budget_public]" value="0">
                            <input type="checkbox" name="funding[budget_public]" value="1" {{ old('funding.budget_public', $fund['budget_public'] ?? false) ? 'checked' : '' }} class="rounded border-gray-300 text-brand focus:ring-brand"> Pokaż budżet publicznie
                        </label>
                    </div>
                    <div>
                        <label for="funding_notice" class="mb-1 block text-sm font-bold">Oznaczenie dofinansowania <span class="font-normal text-muted">(obowiązkowa klauzula grantodawcy, opcjonalnie)</span></label>
                        <textarea id="funding_notice" name="funding_notice" rows="3" maxlength="1000" class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">{{ old('funding_notice', $project->funding_notice) }}</textarea>
                    </div>
                </div>
            </div>
            @endif

            {{-- ==================== KOORDYNATOR I ARCHIWUM ==================== --}}
            <div data-ftab-panel="dodatkowe" class="hidden space-y-6">
                <div class="space-y-5 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-bold uppercase tracking-wide text-muted">Koordynator projektu</p>
                    <p class="-mt-3 text-xs text-muted">Widoczny jako kontakt do projektu na jego stronie. Jeśli nie podasz e-maila koordynatora, wyświetli się ogólny e-mail kontaktowy fundacji.</p>

                    <div class="grid gap-5 sm:grid-cols-3">
                        <div>
                            <label for="coordinator_name" class="mb-1 block text-sm font-bold">Imię i nazwisko <span class="font-normal text-muted">(opcjonalnie)</span></label>
                            <input type="text" id="coordinator_name" name="coordinator_name" value="{{ old('coordinator_name', $project->coordinator_name) }}"
                                class="w-full rounded-lg border-gray-300 focus:border-brand focus:ring-brand">
                            @error('coordinator_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="coordinator_email" class="mb-1 block text-sm font-bold">E-mail <span class="font-normal text-muted">(opcjonalnie)</span></label>
                            <input type="email" id="coordinator_email" name="coordinator_email" value="{{ old('coordinator_email', $project->coordinator_email) }}"
                                placeholder="zostanie użyty ogólny e-mail fundacji"
                                class="w-full rounded-lg border-gray-300 focus:border-brand focus:ring-brand">
                            @error('coordinator_email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="coordinator_phone" class="mb-1 block text-sm font-bold">Telefon <span class="font-normal text-muted">(opcjonalnie)</span></label>
                            <input type="text" id="coordinator_phone" name="coordinator_phone" value="{{ old('coordinator_phone', $project->coordinator_phone) }}"
                                class="w-full rounded-lg border-gray-300 focus:border-brand focus:ring-brand">
                            @error('coordinator_phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <label class="flex items-start gap-2 border-t border-gray-100 pt-4">
                        <input type="hidden" name="show_coordinator" value="0">
                        <input type="checkbox" name="show_coordinator" value="1" {{ old('show_coordinator', $project->show_coordinator ?? true) ? 'checked' : '' }}
                            class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
                        <span class="text-sm font-bold">Pokazuj koordynatora
                            <span class="block font-normal text-muted">Gdy wyłączone, dane koordynatora nie pojawią się na stronie projektu ani na stronie „Kontakt". Można też wyłączyć globalnie w Ustawienia → Kontakt.</span>
                        </span>
                    </label>

                    <label class="flex items-start gap-2 border-t border-gray-100 pt-4">
                        <input type="checkbox" name="is_featured_contact" value="1" {{ old('is_featured_contact', $project->is_featured_contact ?? false) ? 'checked' : '' }}
                            class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
                        <span class="text-sm font-bold">Wyróżniony kontakt
                            <span class="block font-normal text-muted">Na stronie „Kontakt" ten koordynator zostanie wyróżniony innym tłem i pokazany jako pierwszy.</span>
                        </span>
                    </label>
                </div>

                <div class="space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-bold uppercase tracking-wide text-muted">Archiwum</p>
                    <label class="flex items-start gap-2">
                        <input type="checkbox" name="show_legacy_box" value="1" {{ old('show_legacy_box', $project->show_legacy_box ?? false) ? 'checked' : '' }}
                            class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
                        <span class="text-sm font-bold">Pokaż informację, że działanie realizowaliśmy przed uruchomieniem nowej strony</span>
                    </label>
                    <div>
                        <label for="legacy_url" class="mb-1 block text-sm font-bold">Link do informacji o projekcie <span class="font-normal text-muted">(opcjonalnie)</span></label>
                        <input type="url" id="legacy_url" name="legacy_url" value="{{ old('legacy_url', $project->legacy_url) }}" placeholder="https://..."
                            class="w-full rounded-lg border-gray-300 focus:border-brand focus:ring-brand">
                        <p class="mt-1 text-xs text-muted">Jeśli podasz link, w boxie pojawi się odnośnik „Zobacz informacje o projekcie".</p>
                        @error('legacy_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- ==================== SEO ==================== --}}
            <div data-ftab-panel="seo" class="hidden space-y-6">
                @include('admin.partials.seo-fields', ['model' => $project])
            </div>
        </div>

        <div class="sticky bottom-0 z-10 -mx-1 flex items-center gap-3 rounded-lg border border-gray-200 bg-white/95 px-4 py-3 shadow-lg backdrop-blur">
            <button type="submit" class="rounded-lg bg-brand px-5 py-2.5 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">Zapisz</button>
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
                    ['bg-brand', 'text-white'].forEach(function (c) { b.classList.toggle(c, active); });
                    ['bg-gray-100', 'text-ink', 'hover:bg-gray-200'].forEach(function (c) { b.classList.toggle(c, !active); });
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
                    dot.className = 'ml-1.5 inline-block h-2 w-2 rounded-md bg-red-500 align-middle';
                    btn.appendChild(dot);
                }
                if (!firstErrorKey) firstErrorKey = key;
            });
            if (firstErrorKey) activate(firstErrorKey);
        })();
    </script>
@endsection
