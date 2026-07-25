@extends('layouts.site')

@section('title', 'Kontakt — ' . $siteSettings->site_name)
@section('meta_description', 'Skontaktuj się z ' . $siteSettings->site_name . '.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Kontakt', 'url' => null],
    ]])
@endsection

@section('content')
    <section class="mx-auto max-w-5xl px-4 py-12">
        <h1 class="mb-6 text-3xl font-bold text-ink">Kontakt</h1>

        @if ($siteSettings->contact_intro)
            <div class="prose mb-8 max-w-2xl text-muted">{!! $siteSettings->contact_intro !!}</div>
        @endif

        <div class="grid gap-10 md:grid-cols-[1fr_300px]">
            <div>
                @if (session('status'))
                    <div class="mb-6 rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('contact.store') }}" class="max-w-xl space-y-5">
                    @csrf

                    <div class="hidden" aria-hidden="true">
                        <label for="website">Zostaw to pole puste</label>
                        <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    <div>
                        <label for="name" class="mb-1 block text-sm font-bold">Imię i nazwisko</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="email" class="mb-1 block text-sm font-bold">E-mail</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="message" class="mb-1 block text-sm font-bold">Wiadomość</label>
                        <textarea id="message" name="message" rows="6" required
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('message') }}</textarea>
                        @error('message') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="flex items-start gap-2">
                            <input type="checkbox" name="rodo_consent" value="1" required {{ old('rodo_consent') ? 'checked' : '' }}
                                class="mt-1 flex-none rounded border-gray-300 text-brand focus:ring-brand">
                            <span class="text-sm text-muted">
                                Wyrażam zgodę na przetwarzanie moich danych osobowych (imienia i adresu e-mail) w celu udzielenia odpowiedzi na przesłaną wiadomość, zgodnie z
                                <a href="{{ route('page.show', 'polityka-prywatnosci') }}" class="font-bold text-brand hover:text-brand-dark">Polityką prywatności</a>.
                                <span aria-hidden="true" class="font-bold text-red-600">*</span>
                            </span>
                        </label>
                        @error('rodo_consent') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <p class="text-xs text-muted">
                        Administratorem Twoich danych osobowych jest {{ $siteSettings->site_name }}. Dane z formularza przetwarzamy wyłącznie w celu obsługi Twojego zapytania.
                        Masz prawo dostępu do danych, ich sprostowania, usunięcia oraz wycofania zgody w dowolnym momencie. Szczegóły znajdziesz w
                        <a href="{{ route('page.show', 'polityka-prywatnosci') }}" class="font-bold text-brand hover:text-brand-dark">Polityce prywatności</a>.
                    </p>

                    <button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark">Wyślij wiadomość</button>
                </form>
            </div>

            <aside aria-label="Dane kontaktowe">
                <p class="mb-5 text-xl font-bold text-ink">{{ $siteSettings->site_name }}</p>
                <ul class="space-y-5">
                        <li>
                            <a href="https://www.google.com/maps?q={{ urlencode($siteSettings->contact_address.', '.$siteSettings->contact_city) }}" target="_blank" rel="noopener"
                                class="group flex items-start gap-3">
                                <span class="flex h-10 w-10 flex-none items-center justify-center rounded-full bg-brand-light text-brand" aria-hidden="true">
                                    <i class="fa-solid fa-location-dot"></i>
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-xs font-bold uppercase tracking-wide text-muted">Adres</span>
                                    <span class="font-medium text-ink group-hover:text-brand">{{ $siteSettings->contact_address }}<br>{{ $siteSettings->contact_city }}</span>
                                </span>
                            </a>
                        </li>
                        <li>
                            <a href="mailto:{{ $siteSettings->contact_email }}" class="group flex items-start gap-3">
                                <span class="flex h-10 w-10 flex-none items-center justify-center rounded-full bg-brand-light text-brand" aria-hidden="true">
                                    <i class="fa-solid fa-envelope"></i>
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-xs font-bold uppercase tracking-wide text-muted">E-mail</span>
                                    <span class="block break-all font-medium text-ink group-hover:text-brand">{{ $siteSettings->contact_email }}</span>
                                </span>
                            </a>
                        </li>
                        @if ($siteSettings->contact_phone)
                            <li>
                                <a href="tel:{{ $siteSettings->contact_phone }}" class="group flex items-start gap-3">
                                    <span class="flex h-10 w-10 flex-none items-center justify-center rounded-full bg-brand-light text-brand" aria-hidden="true">
                                        <i class="fa-solid fa-phone"></i>
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block text-xs font-bold uppercase tracking-wide text-muted">Telefon</span>
                                        <span class="font-medium text-ink group-hover:text-brand">{{ $siteSettings->contact_phone }}</span>
                                    </span>
                                </a>
                            </li>
                        @endif
                    </ul>

                    @if ($siteSettings->contactBoxIsVisible())
                        <div class="mt-8 rounded-xl border border-brand/30 bg-brand-light p-5">
                            @if ($siteSettings->contact_box_text)
                                <p class="text-sm text-ink">{{ $siteSettings->contact_box_text }}</p>
                            @endif
                            @if ($siteSettings->contact_box_link_url && $siteSettings->contact_box_link_label)
                                @php $external = \Illuminate\Support\Str::startsWith($siteSettings->contact_box_link_url, ['http://', 'https://']); @endphp
                                <a href="{{ $siteSettings->contact_box_link_url }}" @if ($external) target="_blank" rel="noopener" @endif
                                    class="{{ $siteSettings->contact_box_text ? 'mt-3' : '' }} inline-flex items-center gap-2 rounded-full bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark">
                                    {{ $siteSettings->contact_box_link_label }}
                                    <i class="fa-solid {{ $external ? 'fa-arrow-up-right-from-square' : 'fa-arrow-right' }}" aria-hidden="true"></i>
                                </a>
                            @endif
                        </div>
                    @endif
            </aside>
        </div>

        @if ($projects->isNotEmpty())
            <div class="mt-12 border-t border-gray-100 pt-8">
                <h2 class="mb-4 text-xl font-bold text-ink"> Koordynatorzy poszczególnych działań</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($projects as $project)
                        <div class="rounded-lg border p-4 text-sm {{ $project->is_featured_contact ? 'border-brand/30 bg-brand-light ring-1 ring-brand/20' : 'border-gray-200' }}">
                            @if ($project->is_featured_contact)
                                <span class="mb-2 inline-flex items-center gap-1 rounded-full bg-brand px-2.5 py-0.5 text-xs font-bold text-white">
                                    <i class="fa-solid fa-star" aria-hidden="true"></i> Wyróżniony kontakt
                                </span>
                            @endif
                            <a href="{{ route('projects.show', $project) }}" class="block font-bold text-ink hover:text-brand">{{ $project->title }}</a>
                            @if ($project->coordinator_name)
                                <p class="text-muted">{{ $project->coordinator_name }}</p>
                            @endif
                            <p><a href="mailto:{{ $project->contactEmail() }}" class="text-brand hover:text-brand-dark">{{ $project->contactEmail() }}</a></p>
                            @if ($project->coordinator_phone)
                                <p><a href="tel:{{ $project->coordinator_phone }}" class="text-brand hover:text-brand-dark">{{ $project->coordinator_phone }}</a></p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </section>
@endsection
