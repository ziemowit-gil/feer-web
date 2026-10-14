@extends('admin.layout')

@section('title', $category->exists ? 'Edytuj kategorię' : 'Nowa kategoria')

@section('content')
    <form method="POST" action="{{ $category->exists ? route('admin.kategorie.update', $category) : route('admin.kategorie.store') }}" class="max-w-xl space-y-5 p-6 rounded-md bg-white">
        @csrf
        @if ($category->exists) @method('PUT') @endif

        <div>
            <label for="name" class="mb-1 block text-sm font-bold">Nazwa</label>
            <input type="text" id="name" name="name" value="{{ old('name', $category->name) }}" required
                class="w-full rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark">
            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="slug" class="mb-1 block text-sm font-bold">Slug (adres URL)</label>
            <div class="flex items-center gap-2">
                <span class="text-sm text-muted">/kategoria/</span>
                <input type="text" id="slug" name="slug" value="{{ old('slug', $category->slug) }}" placeholder="zostanie wygenerowany z nazwy"
                    class="w-full rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark">
            </div>
            @error('slug') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        @if (\Illuminate\Support\Facades\Schema::hasColumn('categories', 'icon'))
            <div>
                <div class="mb-1 flex items-center"><label for="icon" class="text-sm font-bold">Ikona <span class="font-normal text-muted">(opcjonalnie)</span></label>@include('admin.partials.help', ['topic' => 'ikona kategorii', 'text' => 'Ikona stoi przy nazwie kategorii na liście działań. Puste pole = ikona dobrana automatycznie do nazwy.'])</div>
                <input type="text" id="icon" name="icon" value="{{ old('icon', $category->icon) }}" data-icon-picker data-icon-format="name" placeholder="{{ $category->exists ? $category->iconValue() : 'np. bi-easel' }}"
                    class="w-full rounded border-gray-500 font-mono text-sm focus:border-brand-dark focus:ring-brand-dark">
                @error('icon') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        @endif

        <div>
            <label for="order" class="mb-1 block text-sm font-bold">Kolejność</label>
            <input type="number" id="order" name="order" min="0" value="{{ old('order', $category->order) }}"
                class="w-28 rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark">
        </div>

        @if (\Illuminate\Support\Facades\Schema::hasColumn('categories', 'portal_slug'))
            @php
                $widgetOrder = array_values(array_unique([...($category->portal_widgets ?? []), ...array_keys(\App\Models\Category::PORTAL_WIDGETS)]));
                $widgetOn = $category->portal_widgets ?? \App\Models\Category::defaultPortalWidgets();
                $sliders = \Illuminate\Support\Facades\Schema::hasTable('sliders') ? \App\Models\Slider::query()->forCurrentSite()->orderBy('name')->get(['id', 'name']) : collect();
                $baseHost = preg_replace('/^www\./', '', (string) parse_url((string) config('app.url'), PHP_URL_HOST));
            @endphp
            <fieldset class="space-y-5 border-t border-gray-100 pt-5">
                <legend class="text-base font-bold text-ink">Portal kategorii</legend>
                <p class="text-sm text-muted">Osobna strona kategorii ze sliderem, działaniami, widżetami i kanałem RSS. Pod krótkim adresem w subdomenie odwiedzający trafią prosto do portalu.</p>

                <div>
                    <label for="portal_slug" class="mb-1 block text-sm font-bold">Adres portalu <span class="font-normal text-muted">(puste = bez portalu)</span></label>
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-muted">/portal/</span>
                        <input type="text" id="portal_slug" name="portal_slug" value="{{ old('portal_slug', $category->portal_slug) }}" placeholder="np. szkolenia" aria-describedby="portal_slug_help"
                            class="w-full rounded border-gray-500 font-mono text-sm focus:border-brand-dark focus:ring-brand-dark">
                    </div>
                    <p id="portal_slug_help" class="mt-1 text-xs text-muted">Krótki adres: <span class="font-mono">{adres}.{{ $baseHost }}</span> przekierowuje na portal. Same małe litery, cyfry i myślniki.</p>
                    @error('portal_slug') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    @if ($category->hasPortal())
                        <p class="mt-2 text-sm"><a href="{{ $category->portalUrl() }}" target="_blank" rel="noopener" class="font-bold text-brand-dark underline underline-offset-2 hover:no-underline">Zobacz portal<span class="sr-only"> (nowa karta)</span></a> · <span class="font-mono text-xs">{{ $category->portalSubdomainUrl() }}</span></p>
                    @endif
                </div>

                <div>
                    <label for="portal_title" class="mb-1 block text-sm font-bold">Tytuł portalu <span class="font-normal text-muted">(puste = nazwa kategorii)</span></label>
                    <input type="text" id="portal_title" name="portal_title" value="{{ old('portal_title', $category->portal_title) }}"
                        class="w-full rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark">
                </div>

                <div>
                    <label for="portal_intro" class="mb-1 block text-sm font-bold">Wstęp</label>
                    <textarea id="portal_intro" name="portal_intro" rows="6" aria-describedby="portal_intro_help" class="w-full rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark">{{ old('portal_intro', $category->portal_intro) }}</textarea>
                    <p id="portal_intro_help" class="mt-1 text-xs text-muted">Czym fundacja zajmuje się w tym obszarze i dla kogo. Akapity oddzielaj pustą linią.</p>
                    @error('portal_intro') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="portal_slider_id" class="mb-1 block text-sm font-bold">Slider</label>
                    <select id="portal_slider_id" name="portal_slider_id" class="w-full rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark">
                        <option value="">Automatyczny — zdjęcia działań z kategorii</option>
                        @foreach ($sliders as $sl)
                            <option value="{{ $sl->id }}" @selected((int) old('portal_slider_id', $category->portal_slider_id) === $sl->id)>{{ $sl->name }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-muted">Własne slajdy przygotujesz w Witryna → Slidery.</p>
                </div>

                <div>
                    <label for="portal_rss_url" class="mb-1 block text-sm font-bold">Zewnętrzny kanał RSS <span class="font-normal text-muted">(opcjonalnie)</span></label>
                    <input type="url" id="portal_rss_url" name="portal_rss_url" value="{{ old('portal_rss_url', $category->portal_rss_url) }}" placeholder="https://…/rss.xml"
                        class="w-full rounded border-gray-500 font-mono text-sm focus:border-brand-dark focus:ring-brand-dark">
                    <p class="mt-1 text-xs text-muted">Najnowsze wpisy z tego kanału pokaże widżet „Zewnętrzny kanał RSS”. Portal ma też własny kanał RSS z aktualnościami i działaniami kategorii.</p>
                    @error('portal_rss_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div x-data="{ order: @js($widgetOrder), labels: @js(\App\Models\Category::PORTAL_WIDGETS),
                        move(i, d) { const j = i + d; if (j < 0 || j >= this.order.length) return; [this.order[i], this.order[j]] = [this.order[j], this.order[i]]; } }">
                    <p class="mb-1 text-sm font-bold" id="portal-widgets-label">Widżety i ich kolejność</p>
                    <input type="hidden" name="portal_widgets_order" :value="order.join(',')">
                    <ul role="list" class="space-y-1" aria-labelledby="portal-widgets-label">
                        <template x-for="(key, i) in order" :key="key">
                            <li class="flex items-center gap-2 rounded bg-gray-50 px-3 py-1.5">
                                <label class="flex min-h-8 flex-1 items-center gap-2 text-sm">
                                    <input type="checkbox" name="portal_widgets[]" :value="key" :checked="@js($widgetOn).includes(key)" class="h-4 w-4 rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                                    <span x-text="labels[key]"></span>
                                </label>
                                <button type="button" @click="move(i, -1)" :disabled="i === 0" class="rounded p-1.5 text-xs text-ink hover:bg-white disabled:opacity-40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark" :aria-label="'W górę: ' + labels[key]"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i></button>
                                <button type="button" @click="move(i, 1)" :disabled="i === order.length - 1" class="rounded p-1.5 text-xs text-ink hover:bg-white disabled:opacity-40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark" :aria-label="'W dół: ' + labels[key]"><i class="fa-solid fa-arrow-down" aria-hidden="true"></i></button>
                            </li>
                        </template>
                    </ul>
                </div>

                @if (\Illuminate\Support\Facades\Schema::hasColumn('categories', 'portal_options'))
                    @php
                        $opt = fn (string $k, $d = '') => old('portal_options.'.$k, $category->portalOption($k, $d));
                        $shortcutsText = old('portal_options.shortcuts', collect($category->portalShortcuts())->map(fn ($s) => $s[0].' | '.$s[1])->implode("\n"));
                        $input = 'w-full rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark';
                    @endphp

                    <fieldset class="space-y-4">
                        <legend class="mb-1 text-sm font-bold">Komunikat nad portalem <span class="font-normal text-muted">(opcjonalnie, np. zapisy, zmiana terminu)</span></legend>
                        <div>
                            <label for="portal_notice" class="mb-1 block text-sm">Treść komunikatu</label>
                            <textarea id="portal_notice" name="portal_options[notice]" rows="2" maxlength="500" class="{{ $input }}">{{ $opt('notice') }}</textarea>
                            @error('portal_options.notice') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="portal_notice_link_label" class="mb-1 block text-sm">Tekst linku</label>
                                <input type="text" id="portal_notice_link_label" name="portal_options[notice_link_label]" value="{{ $opt('notice_link_label') }}" placeholder="np. Zapisz się" class="{{ $input }}">
                            </div>
                            <div>
                                <label for="portal_notice_link_url" class="mb-1 block text-sm">Adres linku</label>
                                <input type="text" id="portal_notice_link_url" name="portal_options[notice_link_url]" value="{{ $opt('notice_link_url') }}" placeholder="https://… albo /strona" class="{{ $input }} font-mono text-sm">
                                @error('portal_options.notice_link_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="space-y-4">
                        <legend class="mb-1 text-sm font-bold">Kontakt do obszaru <span class="font-normal text-muted">(widżet „Kontakt”, obok koordynatorów działań)</span></legend>
                        <div>
                            <label for="portal_contact_text" class="mb-1 block text-sm">Kilka słów zachęty</label>
                            <textarea id="portal_contact_text" name="portal_options[contact_text]" rows="2" maxlength="1000" placeholder="np. Masz pytanie o szkolenia? Napisz albo zadzwoń." class="{{ $input }}">{{ $opt('contact_text') }}</textarea>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="portal_contact_email" class="mb-1 block text-sm">E-mail</label>
                                <input type="email" id="portal_contact_email" name="portal_options[contact_email]" value="{{ $opt('contact_email') }}" autocomplete="off" class="{{ $input }}">
                                @error('portal_options.contact_email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="portal_contact_phone" class="mb-1 block text-sm">Telefon</label>
                                <input type="text" id="portal_contact_phone" name="portal_options[contact_phone]" value="{{ $opt('contact_phone') }}" autocomplete="off" class="{{ $input }}">
                            </div>
                        </div>
                    </fieldset>

                    <div>
                        <label for="portal_shortcuts" class="mb-1 block text-sm font-bold">Na skróty <span class="font-normal text-muted">(puste = lista domyślna)</span></label>
                        <textarea id="portal_shortcuts" name="portal_options[shortcuts]" rows="4" aria-describedby="portal_shortcuts_help" placeholder="Zapisy na szkolenia | /formularz-zapisow" class="{{ $input }} font-mono text-sm">{{ $shortcutsText }}</textarea>
                        <p id="portal_shortcuts_help" class="mt-1 text-xs text-muted">Jeden link w wierszu: <span class="font-mono">Etykieta | adres</span>. Adres zaczyna się od https:// albo / (strona w serwisie). Najwyżej 12 linków.</p>
                        @error('portal_options.shortcuts') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <fieldset class="space-y-4">
                        <legend class="mb-1 text-sm font-bold">Sekcja „Pozostańmy w kontakcie” na końcu portalu</legend>
                        <input type="hidden" name="portal_options[stay_enabled]" value="0">
                        <label class="flex min-h-8 items-center gap-2 text-sm">
                            <input type="checkbox" name="portal_options[stay_enabled]" value="1" @checked((bool) old('portal_options.stay_enabled', $category->portalOption('stay_enabled', true))) class="h-4 w-4 rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                            Pokazuj sekcję
                        </label>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="portal_stay_title" class="mb-1 block text-sm">Nagłówek</label>
                                <input type="text" id="portal_stay_title" name="portal_options[stay_title]" value="{{ $opt('stay_title') }}" placeholder="Pozostańmy w kontakcie" class="{{ $input }}">
                            </div>
                            <div>
                                <label for="portal_stay_text" class="mb-1 block text-sm">Krótki opis <span class="text-muted">(opcjonalnie)</span></label>
                                <input type="text" id="portal_stay_text" name="portal_options[stay_text]" value="{{ $opt('stay_text') }}" class="{{ $input }}">
                            </div>
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend class="mb-1 text-sm font-bold">Nagłówki widżetów <span class="font-normal text-muted">(puste = domyślny)</span></legend>
                        <div class="grid gap-3 sm:grid-cols-2">
                            @foreach (\App\Models\Category::PORTAL_WIDGET_TITLES as $wKey => $wDefault)
                                <div>
                                    <label for="portal_title_{{ $wKey }}" class="mb-1 block text-xs text-muted">{{ \App\Models\Category::PORTAL_WIDGETS[$wKey] }}</label>
                                    <input type="text" id="portal_title_{{ $wKey }}" name="portal_options[titles][{{ $wKey }}]" value="{{ old('portal_options.titles.'.$wKey, $category->portalOption('titles.'.$wKey)) }}" placeholder="{{ $wDefault }}" class="{{ $input }} text-sm">
                                </div>
                            @endforeach
                        </div>
                    </fieldset>
                @endif
            </fieldset>
        @endif

        <div class="flex items-center gap-3 border-t border-gray-100 pt-5">
            <button type="submit" class="rounded bg-brand-dark px-5 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">Zapisz</button>
            <a href="{{ route('admin.kategorie.index') }}" class="text-sm text-muted hover:text-brand-dark">Anuluj</a>
        </div>
    </form>
@endsection
