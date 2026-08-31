@extends('layouts.site')

@section('title', $organization->name . ' — ' . $siteSettings->site_name)
@section('meta_description', $organization->description ?: ($organization->name . ' — organizacja członkowska ' . $siteSettings->site_name . '.'))

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Organizacje członkowskie', 'url' => route('federation.organizations')],
        ['label' => $organization->name, 'url' => null],
    ]])
@endsection

@section('content')
    <section class="mx-auto max-w-3xl px-4 py-12 lg:py-16">
        <a href="{{ route('federation.organizations') }}"
            class="mb-6 inline-flex items-center gap-1.5 text-sm font-bold text-brand hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
            Wszystkie organizacje
        </a>

        <div class="rounded-lg border border-gray-100 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex items-start gap-4">
                <span class="flex h-14 w-14 flex-none items-center justify-center rounded-full bg-brand-light text-brand" aria-hidden="true">
                    <i class="fa-solid fa-people-roof text-xl"></i>
                </span>
                <div class="min-w-0">
                    <h1 class="text-2xl font-extrabold leading-tight tracking-tight text-ink sm:text-3xl">{{ $organization->name }}</h1>
                    <p class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-muted">
                        <span class="rounded-full bg-gray-100 px-2.5 py-0.5 font-semibold text-ink/70">{{ $organization->type }}</span>
                        <span><i class="fa-solid fa-location-dot mr-1" aria-hidden="true"></i>{{ $organization->town }}</span>
                        @foreach ($organization->spheres ?? [] as $sphere)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-light px-2.5 py-0.5 font-semibold text-brand">
                                <i class="fa-solid {{ \App\Models\Organization::SPHERE_ICONS[$sphere] ?? 'fa-circle-info' }}" aria-hidden="true"></i>
                                {{ $sphere }}
                            </span>
                        @endforeach
                    </p>
                </div>
            </div>

            @php $photos = $organization->getMedia('photos'); @endphp
            @if ($photos->isNotEmpty())
                <ul class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3" role="list">
                    @foreach ($photos as $photo)
                        <li>
                            <img src="{{ $photo->getAvailableUrl(['thumb']) }}"
                                alt="Zdjęcie z działalności organizacji {{ $organization->name }} ({{ $loop->iteration }} z {{ $loop->count }})"
                                class="h-32 w-full rounded-lg border border-gray-200 object-cover sm:h-36">
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($organization->bio || $organization->description)
                <div class="prose prose-sm mt-6 max-w-none text-ink">
                    <p>{{ $organization->bio ?: $organization->description }}</p>
                </div>
            @endif

            @if ($organization->website_url || $organization->email || $organization->phone)
                <dl class="mt-6 grid gap-3 border-t border-gray-100 pt-6 sm:grid-cols-2">
                    @if ($organization->website_url)
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wide text-muted">Strona internetowa</dt>
                            <dd class="mt-0.5 text-sm">
                                <a href="{{ $organization->website_url }}" target="_blank" rel="noopener"
                                    class="font-semibold text-brand hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                                    {{ $organization->website_url }}
                                    <span class="sr-only">(otwiera się w nowej karcie)</span>
                                </a>
                            </dd>
                        </div>
                    @endif
                    @if ($organization->email)
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wide text-muted">E-mail</dt>
                            <dd class="mt-0.5 text-sm">
                                <a href="mailto:{{ $organization->email }}" class="font-semibold text-brand hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">{{ $organization->email }}</a>
                            </dd>
                        </div>
                    @endif
                    @if ($organization->phone)
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wide text-muted">Telefon</dt>
                            <dd class="mt-0.5 text-sm">
                                <a href="tel:{{ $organization->phone }}" class="font-semibold text-brand hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">{{ $organization->phone }}</a>
                            </dd>
                        </div>
                    @endif
                </dl>
            @endif

            <div class="mt-6 flex flex-wrap items-center gap-3">
                <a href="{{ $organization->mapUrl() }}" target="_blank" rel="noopener"
                    class="inline-flex items-center gap-1.5 rounded-md border-2 px-4 py-2 text-sm font-extrabold transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                    style="border-color:{{ $siteSettings->brandColorN(1) }}; color:{{ $siteSettings->brandColorN(1) }}; --tw-ring-color:{{ $siteSettings->brandColorN(1) }}">
                    <i class="fa-solid fa-map-location-dot" aria-hidden="true"></i>
                    Zobacz na mapie
                    <span class="sr-only">(otwiera się w nowej karcie)</span>
                </a>

                @if ($organization->facebook_url)
                    <a href="{{ $organization->facebook_url }}" target="_blank" rel="noopener"
                        class="flex h-10 w-10 items-center justify-center rounded-full border border-gray-200 text-muted transition hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                        <i class="fa-brands fa-facebook" aria-hidden="true"></i>
                        <span class="sr-only">Facebook organizacji {{ $organization->name }} (otwiera się w nowej karcie)</span>
                    </a>
                @endif
                @if ($organization->instagram_url)
                    <a href="{{ $organization->instagram_url }}" target="_blank" rel="noopener"
                        class="flex h-10 w-10 items-center justify-center rounded-full border border-gray-200 text-muted transition hover:border-brand hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                        <i class="fa-brands fa-instagram" aria-hidden="true"></i>
                        <span class="sr-only">Instagram organizacji {{ $organization->name }} (otwiera się w nowej karcie)</span>
                    </a>
                @endif
            </div>
        </div>
    </section>
@endsection
