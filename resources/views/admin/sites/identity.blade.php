@extends('admin.layout')

@section('title', 'Zmień nazwę i adres witryny')

@section('content')
    @php
        $root = rtrim((string) config('app.url'), '/');
        $field = 'min-h-11 w-full rounded border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark';
    @endphp

    <p class="mb-4 text-sm">
        <a href="{{ route('admin.witryny.index') }}" class="font-bold text-brand-dark underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Zarządzaj subwitrynami
        </a>
    </p>

    <form method="POST" action="{{ route('admin.witryny.nazwa.update', $site) }}" class="max-w-xl space-y-5 rounded-md bg-white p-6"
          x-data="{
              slug: @js((string) old('slug', $site->slug)),
              domain: @js((string) old('domain', $site->domain)),
              root: @js($root),
              get url() {
                  const host = this.domain.trim().toLowerCase().replace(/^[a-z][a-z0-9+.-]*:\/\//, '').split('/')[0].replace(/^[ .]+|[ .]+$/g, '');
                  if (host) { return 'https://' + host; }
                  const slug = this.slug.trim().toLowerCase();
                  return slug ? this.root + '/' + slug : '';
              },
          }">
        @csrf
        @method('PUT')

        <div>
            <label for="site_name" class="mb-1 block text-sm font-bold text-ink">Nazwa witryny</label>
            <input type="text" id="site_name" name="site_name" value="{{ old('site_name', $site->site_name) }}" required autocomplete="off"
                   @error('site_name') aria-invalid="true" aria-describedby="site_name_error" @enderror class="{{ $field }}">
            @error('site_name') <p id="site_name_error" class="mt-1 text-sm text-red-700" role="alert">{{ $message }}</p> @enderror
        </div>

        @if ($isMain)
            <p class="rounded bg-gray-50 px-4 py-3 text-sm text-ink">
                Adres witryny głównej to adres serwera ({{ $root }}), więc nie zmienia się tutaj — zmienia się go w konfiguracji serwera.
            </p>
        @else
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="slug" class="mb-1 block text-sm font-bold text-ink">Adres skrócony</label>
                    <div class="flex items-center gap-1 text-sm text-muted">
                        <span class="shrink-0">{{ parse_url($root, PHP_URL_HOST) ?: request()->getHttpHost() }}/</span>
                        <input type="text" id="slug" name="slug" x-model="slug" value="{{ old('slug', $site->slug) }}" placeholder="osrodek" autocomplete="off" autocapitalize="none" spellcheck="false"
                               aria-describedby="slug_help @error('slug') slug_error @enderror" @error('slug') aria-invalid="true" @enderror class="{{ $field }}">
                    </div>
                    <p id="slug_help" class="mt-1 text-xs text-muted">Litery bez polskich znaków, cyfry i myślniki, np. szkolenia.</p>
                    @error('slug') <p id="slug_error" class="mt-1 text-sm text-red-700" role="alert">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="domain" class="mb-1 block text-sm font-bold text-ink">Własna domena (opcjonalnie)</label>
                    <input type="text" id="domain" name="domain" x-model="domain" value="{{ old('domain', $site->domain) }}" placeholder="np. szkolenia.przyklad.pl" autocomplete="off" autocapitalize="none" spellcheck="false"
                           aria-describedby="domain_help @error('domain') domain_error @enderror" @error('domain') aria-invalid="true" @enderror class="{{ $field }}">
                    <p id="domain_help" class="mt-1 text-xs text-muted">Sama domena, bez http:// i ukośników. Musi wskazywać (DNS) na ten serwer; ma pierwszeństwo przed adresem skróconym.</p>
                    @error('domain') <p id="domain_error" class="mt-1 text-sm text-red-700" role="alert">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="rounded bg-brand-light px-4 py-3 text-sm text-ink">
                <p class="font-bold">Adres po zapisaniu</p>
                <p class="mt-1 break-all" aria-live="polite">
                    <span x-show="url" x-text="url"></span>
                    <span x-show="! url">Witryna będzie niedostępna publicznie — wpisz adres skrócony albo domenę.</span>
                </p>
                <p class="mt-2 text-xs text-ink">Obecnie: {{ ($site->domain || $site->slug) ? current_site_url($site) : 'brak adresu' }}</p>
            </div>

            <p class="rounded bg-gray-50 px-4 py-3 text-sm text-ink">
                <i class="fa-solid fa-circle-info mr-1" aria-hidden="true"></i>
                Zmiana adresu zmienia linki do tej witryny, ale dotychczasowy adres będzie przekierowywał na nowy (razem
                ze ścieżką i parametrami), więc stare linki nadal zadziałają. Mimo to zaktualizuj odnośniki w menu,
                newsletterach i na zewnętrznych stronach. Przekierowanie ze starej domeny działa tylko wtedy, gdy ta domena
                nadal wskazuje (DNS) na ten serwer.
            </p>
        @endif

        <div class="flex flex-wrap items-center gap-3 border-t border-gray-100 pt-5">
            <button type="submit" class="inline-flex min-h-11 items-center rounded bg-brand-dark px-5 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">Zapisz</button>
            <a href="{{ route('admin.witryny.index') }}" class="inline-flex min-h-11 items-center px-3 text-sm font-bold text-ink hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">Anuluj</a>
            @unless ($isMain)
                <a href="{{ route('admin.witryny.edit', $site) }}" class="ml-auto inline-flex min-h-11 items-center px-3 text-sm text-muted hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">Pełna edycja (logo, szablon, kolor)</a>
            @endunless
        </div>
    </form>

    {{-- Dawne adresy subwitryny (SiteAddressRedirect): przekierowują na obecny adres; osobny formularz przy każdym, więc poza głównym. --}}
    @unless ($isMain)
        <section aria-labelledby="h-old-addresses" class="mt-6 max-w-xl rounded-md bg-white p-6">
            <h2 id="h-old-addresses" class="text-base font-bold text-ink">Przekierowania ze starych adresów</h2>

            @if ($redirects->isEmpty())
                <p class="mt-2 text-sm text-ink">
                    Nie ma jeszcze żadnych. Gdy zmienisz adres skrócony albo domenę, dotychczasowy adres pojawi się tutaj
                    i będzie przekierowywał na nowy.
                </p>
            @else
                <p class="mt-2 text-sm text-ink">
                    Te adresy przekierowują na obecny adres witryny razem ze ścieżką i parametrami. Usuń przekierowanie,
                    gdy stary adres ma przestać działać.
                </p>
                <ul class="mt-3 divide-y divide-gray-100" role="list">
                    @foreach ($redirects as $redirect)
                        @php $blocked = $redirect->blockedReason(); @endphp
                        {{-- min-w-48 (nie min-w-0): bez realnej szerokości bazowej wiersz nie zawija przycisku pod adres i na 320 px adres ma ~28 px. --}}
                        <li class="flex flex-wrap items-center gap-3 py-3">
                            <div class="min-w-48 flex-1">
                                <p class="break-all font-bold text-ink">{{ $redirect->displayAddress() }}</p>
                                <p class="text-xs text-muted">{{ $redirect->typeLabel() }} · od {{ $redirect->created_at?->format('d.m.Y') }}</p>
                                @if ($blocked)
                                    <p class="mt-1 text-xs font-bold text-ink">
                                        <i class="fa-solid fa-circle-exclamation mr-1" aria-hidden="true"></i>Nie działa teraz: {{ $blocked }}. Przekierowanie wróci, gdy ten adres się zwolni.
                                    </p>
                                @endif
                            </div>
                            <form method="POST" action="{{ route('admin.witryny.przekierowania.destroy', [$site, $redirect]) }}"
                                  data-confirm="Usunąć przekierowanie z adresu {{ $redirect->displayAddress() }}? Stary link przestanie działać.">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex min-h-11 items-center gap-2 rounded px-4 text-sm font-bold text-red-800 hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-700">
                                    <i class="fa-solid fa-trash" aria-hidden="true"></i> Usuń przekierowanie<span class="sr-only"> z adresu {{ $redirect->displayAddress() }}</span>
                                </button>
                            </form>
                        </li>
                    @endforeach
                </ul>
                @unless ($site->domain || $site->slug)
                    <p class="mt-3 rounded bg-gray-50 px-4 py-3 text-sm text-ink">
                        Witryna nie ma teraz adresu, więc te przekierowania nie działają. Ustaw adres skrócony albo domenę.
                    </p>
                @endunless
            @endif
        </section>
    @endunless
@endsection
