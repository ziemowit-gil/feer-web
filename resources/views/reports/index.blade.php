@extends('layouts.site')

@section('title', 'Sprawozdania roczne — ' . $siteSettings->site_name)
@section('meta_description', 'Roczne sprawozdania merytoryczne i finansowe ' . $siteSettings->site_name . ' — do pobrania w formacie PDF.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Sprawozdania', 'url' => null],
    ]])
@endsection

@section('content')
    <section class="mx-auto max-w-4xl px-4 py-14">
        <h1 class="mb-3 text-center text-3xl font-bold text-ink md:text-4xl">Sprawozdania roczne</h1>
        <p class="mx-auto mb-12 max-w-2xl text-center text-muted">
            Publikujemy roczne sprawozdania merytoryczne i finansowe, żeby każdy mógł zobaczyć, co robimy i na co idą środki.
        </p>

        @forelse ($reports as $report)
            @php($files = $report->additionalFiles())
            <div class="mb-10">
                <h2 id="rok-{{ $report->year }}" class="mb-4 flex items-baseline gap-3 text-2xl font-bold text-ink">
                    <span class="flex h-9 items-center rounded-lg bg-brand-light px-3 text-brand">{{ $report->year }}</span>
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
                                    class="group flex h-full items-center gap-4 rounded-2xl border-2 border-gray-200 bg-white p-5 transition hover:border-brand hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                                    <span class="flex h-12 w-12 flex-none items-center justify-center rounded-xl bg-brand-light text-brand transition group-hover:bg-white" aria-hidden="true">
                                        <i class="fa-solid fa-file-lines text-lg"></i>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block font-bold text-ink">{{ $label }}</span>
                                        <span class="block text-sm text-muted">Kliknij, aby pobrać (PDF)</span>
                                    </span>
                                    <i class="fa-solid fa-download flex-none text-brand" aria-hidden="true"></i>
                                    <span class="sr-only">{{ $label }} za {{ $report->year }} rok (PDF)</span>
                                </a>
                            @else
                                <div class="flex h-full items-center gap-4 rounded-2xl border-2 border-dashed border-gray-200 bg-gray-50 p-5">
                                    <span class="flex h-12 w-12 flex-none items-center justify-center rounded-xl bg-gray-100 text-muted" aria-hidden="true">
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
                                class="group flex h-full items-center gap-4 rounded-2xl border-2 border-gray-200 bg-white p-5 transition hover:border-brand hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                                <span class="flex h-12 w-12 flex-none items-center justify-center rounded-xl bg-brand-light text-brand transition group-hover:bg-white" aria-hidden="true">
                                    <i class="fa-solid {{ $report->fileIcon($media) }} text-lg"></i>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block font-bold text-ink">{{ $media->name }}</span>
                                    <span class="block text-sm text-muted">{{ strtoupper($media->extension) }} · {{ $media->human_readable_size }} · pobierz</span>
                                </span>
                                <i class="fa-solid fa-download flex-none text-brand" aria-hidden="true"></i>
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
