@extends('layouts.site')

@section('title', 'Sprawozdania roczne — ' . $siteSettings->site_name)
@section('meta_description', 'Roczne sprawozdania merytoryczne i finansowe ' . $siteSettings->site_name . ' — do pobrania w formacie PDF.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Sprawozdania', 'url' => null],
    ]])
@endsection

@section('content')
    <div class="border-b border-gray-100 bg-gray-50">
        <div class="mx-auto max-w-6xl px-4 py-12 md:py-16">
            <h1 class="text-3xl font-extrabold leading-tight tracking-tight text-ink md:text-5xl">Sprawozdania roczne</h1>
            <p class="mt-4 max-w-2xl text-lg leading-relaxed text-ink">
                Publikujemy roczne sprawozdania merytoryczne i finansowe, żeby każdy mógł zobaczyć, co robimy i na co idą środki.
            </p>
        </div>
    </div>

    <section class="mx-auto max-w-6xl px-4 py-12">

        @forelse ($reports as $report)
            @php($files = $report->additionalFiles())
            <div class="mb-10">
                <h2 id="rok-{{ $report->year }}" class="mb-4 flex items-baseline gap-3 border-b border-gray-200 pb-3 text-2xl font-bold text-ink">
                    <span>{{ $report->year }}</span>
                    <span class="text-base font-medium text-muted">rok sprawozdawczy</span>
                </h2>

                <ul aria-labelledby="rok-{{ $report->year }}" class="grid gap-4 sm:grid-cols-2">
                    {{-- Dwa sprawozdania roczne --}}
                    @foreach (\App\Models\AnnualReport::TYPES as $type => $label)
                        @php($url = $report->fileUrlFor($type))
                        @php($message = $report->messageFor($type))
                        <li>
                            @if ($url)
                                <a href="{{ $url }}" target="_blank" rel="noopener" download
                                    class="group flex h-full items-center gap-4 rounded-lg border border-gray-200 bg-white p-5 transition hover:border-brand hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                                    <span class="flex h-10 w-10 flex-none items-center justify-center text-brand-dark" aria-hidden="true">
                                        <i class="fa-solid fa-file-lines text-lg"></i>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block font-bold text-ink">{{ $label }}</span>
                                        <span class="block text-sm text-muted">Kliknij, aby pobrać (PDF)</span>
                                    </span>
                                    <i class="fa-solid fa-download flex-none text-brand-dark" aria-hidden="true"></i>
                                    <span class="sr-only">{{ $label }} za {{ $report->year }} rok (PDF)</span>
                                </a>
                            @else
                                <div class="flex h-full items-center gap-4 rounded-lg border border-dashed border-gray-300 bg-gray-50 p-5">
                                    <span class="flex h-10 w-10 flex-none items-center justify-center text-muted" aria-hidden="true">
                                        <i class="fa-solid fa-file-lines text-lg"></i>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block font-bold text-ink">{{ $label }}</span>
                                        <span class="block text-sm text-muted">{{ $message }}</span>
                                    </span>
                                </div>
                            @endif
                        </li>
                    @endforeach

                    {{-- Dodatkowe pliki (opcjonalne) --}}
                    @foreach ($files as $media)
                        <li>
                            <a href="{{ $media->getUrl() }}" target="_blank" rel="noopener" download
                                class="group flex h-full items-center gap-4 rounded-lg border border-gray-200 bg-white p-5 transition hover:border-brand hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                                <span class="flex h-10 w-10 flex-none items-center justify-center text-brand-dark" aria-hidden="true">
                                    <i class="fa-solid {{ $report->fileIcon($media) }} text-lg"></i>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block font-bold text-ink">{{ $media->name }}</span>
                                    <span class="block text-sm text-muted">{{ strtoupper($media->extension) }} · {{ $media->human_readable_size }} · pobierz</span>
                                </span>
                                <i class="fa-solid fa-download flex-none text-brand-dark" aria-hidden="true"></i>
                                <span class="sr-only">{{ $media->name }} — plik dodatkowy za {{ $report->year }} rok</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @empty
            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-10 text-center text-muted">
                Nie opublikowaliśmy jeszcze żadnych sprawozdań. Zajrzyj wkrótce.
            </div>
        @endforelse
    </section>
@endsection
