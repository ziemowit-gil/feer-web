@extends('admin.layout')

@section('title', $site->exists ? 'Edytuj witrynę' : 'Nowa sub-witryna')

@section('content')
    <form method="POST" action="{{ $site->exists ? route('admin.witryny.update', $site) : route('admin.witryny.store') }}"
        enctype="multipart/form-data" class="max-w-xl space-y-5 p-6 rounded-md bg-white">
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
                class="block w-full cursor-pointer text-sm text-muted file:mr-3 file:cursor-pointer file:rounded file:border-0 file:bg-brand-dark file:px-4 file:py-2 file:text-sm file:font-bold file:text-white hover:file:bg-brand-dark">
            @error('logo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="site_name" class="mb-1 block text-sm font-bold">Nazwa witryny</label>
            <input type="text" id="site_name" name="site_name" value="{{ old('site_name', $site->site_name) }}" required
                class="w-full rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark">
            @error('site_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="tagline" class="mb-1 block text-sm font-bold">Hasło / podtytuł (opcjonalnie)</label>
            <input type="text" id="tagline" name="tagline" value="{{ old('tagline', $site->tagline) }}"
                class="w-full rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark">
        </div>

        <div>
            <label for="brand_color" class="mb-1 block text-sm font-bold">Kolor marki (opcjonalnie)</label>
            <input type="text" id="brand_color" name="brand_color" value="{{ old('brand_color', $site->brand_color) }}" placeholder="#1d4ed8"
                class="w-40 rounded border-gray-500 font-mono focus:border-brand-dark focus:ring-brand-dark">
            <p class="mt-1 text-xs text-muted">Format hex, np. #1d4ed8. Puste = kolor marki głównej witryny.</p>
            @error('brand_color') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Szablon witryny: domyślnie jak witryna nadrzędna; lista jak w Ustawieniach → Szablon (blokady instalacji, wersja płatna). --}}
        <div>
            @php
                $tplCurrent = old('site_template', $site->exists ? ($site->site_template ?? 'default') : (\App\Models\SiteSetting::current()->site_template ?? 'default'));
                $tplBlocked = \App\Models\SiteSetting::current()->blocked_options['site_templates'] ?? [];
            @endphp
            <label for="site_template" class="mb-1 block text-sm font-bold">Szablon witryny</label>
            <select id="site_template" name="site_template" aria-describedby="site_template_help"
                class="w-full rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark">
                @foreach (\App\Models\SiteSetting::SITE_TEMPLATES as $tplValue => $tplLabel)
                    @continue(in_array($tplValue, $tplBlocked, true) && $tplValue !== $tplCurrent)
                    @php $tplLocked = ! \App\Support\Edition::allowsTemplate($tplValue) && $tplValue !== ($site->site_template ?? null); @endphp
                    <option value="{{ $tplValue }}" @selected($tplCurrent === $tplValue) @disabled($tplLocked)>{{ $tplLabel }}{{ $tplLocked ? ' — wersja płatna' : '' }}</option>
                @endforeach
            </select>
            <p id="site_template_help" class="mt-1 text-xs text-muted">{{ $site->exists ? 'Zmiana szablonu nie usuwa treści witryny. Szczegóły szablonu ustawisz po przełączeniu się na witrynę (Ustawienia → Szablon).' : 'Domyślnie taki jak witryny nadrzędnej. Szczegóły szablonu ustawisz po przełączeniu się na nową witrynę (Ustawienia → Szablon).' }}</p>
            @error('site_template') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        @if (! $site->exists || $site->parent_site_id)
            @php
                $siteKind = old('site_kind', $site->site_kind ?? 'standard');
                $parentForBip = $site->exists ? $site->parentSite : \App\Models\SiteSetting::current();
                $parentBipCount = $parentForBip ? \App\Models\BipDocument::withTrashed()->where('site_id', $parentForBip->id)->count() : 0;
            @endphp
            <fieldset x-data="{ kind: @js($siteKind) }">
                <legend class="mb-1 block text-sm font-bold">Rodzaj witryny</legend>
                <div class="space-y-2">
                    @foreach (\App\Models\SiteSetting::SITE_KINDS as $kindKey => $kindLabel)
                        <label class="flex items-start gap-2 text-sm">
                            <input type="radio" name="site_kind" value="{{ $kindKey }}" x-model="kind" @checked($siteKind === $kindKey)
                                aria-describedby="site_kind_{{ $kindKey }}_desc"
                                class="mt-0.5 border-gray-500 text-brand-dark focus:ring-brand-dark">
                            <span>
                                <span class="font-bold text-ink">{{ $kindLabel }}</span>
                                <span id="site_kind_{{ $kindKey }}_desc" class="block text-muted">
                                    @if ($kindKey === 'bip')
                                        Stroną główną jest Biuletyn Informacji Publicznej z własnymi dokumentami, logo i nagłówkiem (np. bip.przyklad.pl). Linki „BIP" na witrynie nadrzędnej prowadzą tutaj, a dotychczasowe adresy /bip przekierowują na tę witrynę.
                                    @else
                                        Własne aktualności, strony i wydarzenia, np. ośrodek prowadzony przez organizację.
                                    @endif
                                </span>
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('site_kind') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

                @if ($parentBipCount > 0)
                    <label x-show="kind === 'bip'" x-cloak class="mt-3 flex items-start gap-2 rounded bg-brand-light px-3 py-2 text-sm text-ink">
                        <input type="checkbox" name="move_bip_content" value="1" @checked(old('move_bip_content', ! $site->exists))
                            class="mt-0.5 rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                        <span>Przenieś do tej witryny dokumenty BIP witryny „{{ $parentForBip->site_name }}" ({{ $parentBipCount }}) i jej menu boczne BIP.</span>
                    </label>
                @endif
            </fieldset>
        @endif

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="slug" class="mb-1 block text-sm font-bold">Adres skrócony (slug)</label>
                <div class="flex items-center gap-1 text-sm text-muted">
                    <span class="shrink-0">{{ request()->getHttpHost() }}/</span>
                    <input type="text" id="slug" name="slug" value="{{ old('slug', $site->slug) }}" placeholder="osrodek"
                        class="w-full rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark">
                </div>
                <p class="mt-1 text-xs text-muted">Witryna będzie też osiągalna pod dłuższym adresem {{ request()->getHttpHost() }}/site/{ten-adres} — obie postacie działają, ta krótsza jest używana w generowanych linkach.</p>
                @error('slug') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="domain" class="mb-1 block text-sm font-bold">Własna domena (opcjonalnie)</label>
                <input type="text" id="domain" name="domain" value="{{ old('domain', $site->domain) }}" placeholder="np. przyklad.pl"
                    class="w-full rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark">
                <p class="mt-1 text-xs text-muted">Domena musi wskazywać (DNS) na ten serwer.</p>
                @error('domain') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex items-center gap-3 border-t border-gray-100 pt-5">
            <button type="submit" class="rounded bg-brand-dark px-5 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">Zapisz</button>
            <a href="{{ route('admin.witryny.index') }}" class="text-sm text-muted hover:text-brand-dark">Anuluj</a>
        </div>
    </form>
@endsection
