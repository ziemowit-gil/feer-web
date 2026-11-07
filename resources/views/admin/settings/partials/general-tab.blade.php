{{--
    Zakładka „Ogólne” (Ustawienia → Ogólne): sekcje zamiast jednej długiej listy pól — „Tożsamość strony” (nazwa, podtytuł),
    „Logo”, „Edycja treści i integracje” (edytor, Unsplash), „Pliki cookies” i „Stopka”. Nazwy pól bez zmian
    (SiteSettingController). Dołączana wewnątrz kontenera `tab === 'general'`.
--}}
<div class="space-y-6">
    <section aria-labelledby="gen-identity-h" class="rounded-lg border border-gray-200 p-5">
        <h2 id="gen-identity-h" class="text-base font-bold text-ink">Tożsamość strony</h2>
        <p class="mt-1 text-xs text-muted">Nazwa i podtytuł widoczne w nagłówku, w tytułach kart przeglądarki i w komunikatach serwisu.</p>
        <div class="mt-4 space-y-5">
        <div>
            <label for="site_name" class="mb-1 block text-sm font-bold">Nazwa strony</label>
            <input type="text" id="site_name" name="site_name" value="{{ old('site_name', $settings->site_name) }}" required
                class="w-full rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark">
            @error('site_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="site_name_genitive" class="mb-1 block text-sm font-bold">
                Nazwa strony w dopełniaczu <span class="font-normal text-muted">(opcjonalnie)</span>
            </label>
            <input type="text" id="site_name_genitive" name="site_name_genitive" value="{{ old('site_name_genitive', $settings->site_name_genitive) }}"
                placeholder="np. Krakowskiego Forum Organizacji Społecznych"
                class="w-full rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark">
            <p class="mt-1 text-xs text-muted">Używana w zdaniach typu „Organizacje …”. Zostaw puste, jeśli nazwa strony nie odmienia się (np. akronim).</p>
            @error('site_name_genitive') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="tagline" class="mb-1 block text-sm font-bold">Podtytuł (tagline)</label>
            <input type="text" id="tagline" name="tagline" value="{{ old('tagline', $settings->tagline) }}"
                class="w-full rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark">
            <p class="mt-1 text-xs text-muted">Wyświetlany w nagłówku pod nazwą strony.</p>
            @error('tagline') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        </div>
    </section>

    <section aria-labelledby="gen-logo-h" class="rounded-lg border border-gray-200 p-5">
        <h2 id="gen-logo-h" class="text-base font-bold text-ink">Logo</h2>
        <p class="mt-1 text-xs text-muted">Znaczek w nagłówku strony. Opis alternatywny czytają czytniki ekranu (WCAG).</p>
        <div class="mt-4 space-y-5">
        <div>
            <p class="mb-1 text-sm font-bold">Logo</p>
            @if ($settings->logoUrl())
                <div class="mb-2 flex items-center gap-3">
                    <img src="{{ $settings->logoUrl() }}" alt="Logo" class="h-14 w-14 rounded object-contain">
                    <label class="flex items-center gap-2 text-sm text-muted">
                        <input type="checkbox" name="remove_logo" value="1" class="rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                        Usuń logo (wróć do domyślnego znaczka)
                    </label>
                </div>
            @endif
            <input type="file" name="logo" accept="image/*" class="block w-full cursor-pointer text-sm text-muted file:mr-3 file:cursor-pointer file:rounded file:border-0 file:bg-brand-dark file:px-4 file:py-2 file:text-sm file:font-bold file:text-white hover:file:bg-brand-dark">
            @error('logo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

            <div class="mt-3">
                <label for="logo_alt" class="mb-1 block text-sm font-bold">Tekst alternatywny logo <span class="font-normal text-muted">(opcjonalnie)</span></label>
                <input type="text" id="logo_alt" name="logo_alt" value="{{ old('logo_alt', $settings->logo_alt) }}"
                    placeholder="{{ $settings->site_name }}"
                    class="w-full rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark">
                <p class="mt-1 text-xs text-muted">Opis logo dla czytników ekranu (WCAG). Puste = nazwa strony.</p>
                @error('logo_alt') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <label class="mt-3 flex items-start gap-2 text-sm">
                <input type="checkbox" name="logo_only" value="1" @checked(old('logo_only', $settings->logo_only)) class="mt-0.5 rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                <span>
                    <span class="font-bold">Pokazuj samo logo</span>
                    <span class="block text-xs text-muted">Ukrywa nazwę strony i podtytuł w nagłówku. Działa tylko, gdy logo jest wgrane.</span>
                </span>
            </label>
        </div>
        </div>
    </section>

    <section aria-labelledby="gen-editing-h" class="rounded-lg border border-gray-200 p-5">
        <h2 id="gen-editing-h" class="text-base font-bold text-ink">Edycja treści i integracje</h2>
        <p class="mt-1 text-xs text-muted">Domyślny edytor treści oraz klucz do wyszukiwania zdjęć w module Multimedia.</p>
        <div class="mt-4 space-y-5">
        <div>
            <label for="content_editor" class="mb-1 block text-sm font-bold">Edytor treści (strony, aktualności, działania)</label>
            <select id="content_editor" name="content_editor" class="w-full rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark">
                @foreach (\App\Models\SiteSetting::EDITORS as $value => $label)
                    <option value="{{ $value }}" {{ old('content_editor', $settings->contentEditorValue()) === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('content_editor') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="unsplash_access_key" class="mb-1 block text-sm font-bold">Klucz Unsplash (Access Key)</label>
            <input type="password" id="unsplash_access_key" name="unsplash_access_key" autocomplete="new-password"
                placeholder="{{ $settings->unsplash_access_key ? '•••••••• (zapisany — zostaw puste, aby nie zmieniać)' : 'Wklej Access Key z unsplash.com/developers' }}"
                class="w-full rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark">
            <p class="mt-1 text-xs text-muted">Umożliwia wyszukiwanie i import zdjęć z Unsplash w module Multimedia. Darmowy klucz: <a href="https://unsplash.com/developers" target="_blank" rel="noopener" class="text-brand-dark underline">unsplash.com/developers</a>. Puste = wartość z pliku <code>.env</code>.</p>
            @error('unsplash_access_key') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        </div>
    </section>

    <section aria-labelledby="gen-cookies-h" class="rounded-lg border border-gray-200 p-5">
        <h2 id="gen-cookies-h" class="text-base font-bold text-ink">Pliki cookies</h2>
        <p class="mt-1 text-xs text-muted">Baner zgody, jego tekst i rejestr decyzji odwiedzających.</p>
        <div class="mt-4 space-y-5">
        <div class="space-y-3 p-4 rounded-md bg-gray-50">
            <label class="flex items-center gap-2">
                <input type="checkbox" name="cookie_banner_enabled" value="1" {{ old('cookie_banner_enabled', $settings->cookie_banner_enabled ?? true) ? 'checked' : '' }}
                    class="rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                <span class="text-sm font-bold">Pokaż baner o plikach cookies</span>
            </label>
            <div>
                <label for="cookie_banner_text" class="mb-1 block text-sm font-bold">Tekst banera <span class="font-normal text-muted">(opcjonalnie)</span></label>
                <textarea id="cookie_banner_text" name="cookie_banner_text" rows="2"
                    placeholder="{{ \App\Models\SiteSetting::DEFAULT_COOKIE_BANNER_TEXT }}"
                    class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">{{ old('cookie_banner_text', $settings->cookie_banner_text) }}</textarea>
                <p class="mt-1 text-xs text-muted">Baner z równorzędnymi przyciskami „Akceptuję" i „Odrzucam" oraz linkiem do polityki prywatności. Decyzję można zmienić linkiem „Ustawienia cookies" w stopce. Puste = tekst domyślny.</p>
                @error('cookie_banner_text') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            @php
                $consentStats = \App\Models\CookieConsent::forCurrentSite()
                    ->where('created_at', '>=', now()->subDays(30))
                    ->selectRaw('decision, count(*) as total')->groupBy('decision')
                    ->pluck('total', 'decision');
            @endphp
            <p class="text-xs text-muted">
                Rejestr zgód, ostatnie 30 dni:
                <strong class="text-ink">{{ $consentStats['accepted'] ?? 0 }}</strong> zgód,
                <strong class="text-ink">{{ $consentStats['rejected'] ?? 0 }}</strong> odmów.
                Każda decyzja jest zapisywana z datą i zanonimizowanym IP (dowód zgody wg RODO).
            </p>
        </div>
        </div>
    </section>

    <section aria-labelledby="gen-footer-h" class="rounded-lg border border-gray-200 p-5">
        <h2 id="gen-footer-h" class="text-base font-bold text-ink">Stopka</h2>
        <p class="mt-1 text-xs text-muted">Podpis autora CMS w stopce strony.</p>
        <div class="mt-4 space-y-5">
        @php $isFeerInstall = str_contains(request()->getHost(), 'feer.org.pl'); @endphp
        <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 p-4">
            <input type="checkbox" name="show_cms_credit" value="1"
                {{ ($isFeerInstall ? old('show_cms_credit', $settings->show_cms_credit ?? true) : true) ? 'checked' : '' }}
                {{ $isFeerInstall ? '' : 'disabled' }}
                class="rounded border-gray-500 text-brand-dark focus:ring-brand-dark disabled:cursor-not-allowed disabled:opacity-60">
            @if (! $isFeerInstall)
                <input type="hidden" name="show_cms_credit" value="1">
            @endif
            <span class="text-sm font-bold">
                Pokaż nazwę CMS w stopce („weCMS · autor…")
                @unless ($isFeerInstall)
                    <span class="ml-1 text-[0.65rem] font-bold text-muted">🔒 Zawsze widoczne poza feer.org.pl</span>
                @endunless
            </span>
        </label>
        </div>
    </section>
</div>
