{{-- Typ „Sprawozdania roczne": treść strony + tabela sprawozdań z modułu Sprawozdania (AnnualReport). --}}
@php
    $reportsOn = $siteSettings->isModuleEnabled('reports');
    $td = $page->typeData();
    $reports = $reportsOn ? \App\Models\AnnualReport::published()->with('media')->get() : collect();
@endphp
<style>
    .rp-tbl { width: 100%; border-collapse: collapse; font-size: .95rem; }
    .rp-tbl th { padding: .6rem .9rem; text-align: left; font-size: .72rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: #1d1d1a; border-bottom: 3px solid #1d1d1a; }
    .rp-tbl td, .rp-tbl tbody th { padding: .8rem .9rem; border-bottom: 1px solid #d1d5db; vertical-align: top; color: #1d1d1a; }
    .rp-tbl tbody th { font-size: 1.1rem; font-weight: 800; }
    .rp-dl { display: inline-flex; min-height: 2.5rem; align-items: center; gap: .45rem; font-weight: 800; color: var(--color-brand-dark); text-decoration: underline; text-underline-offset: 3px; }
    .rp-dl:hover { color: #1d1d1a; } .rp-dl:focus-visible { outline: 3px solid #1d1d1a; outline-offset: 2px; border-radius: .25rem; }
    .rp-extra { margin: .35rem 0 0; padding: 0; list-style: none; font-size: .85rem; }
</style>
<section class="mx-auto max-w-5xl px-4 py-8">
    @include('page.partials.typed._head')

    @if (! $reportsOn)
        <p class="text-muted">Moduł „Sprawozdania” jest wyłączony — włącz go w Ustawienia → Moduły, aby pokazać tabelę.</p>
    @elseif ($reports->isEmpty())
        <p class="text-muted">Sprawozdania zostaną wkrótce opublikowane.</p>
    @else
        <div class="overflow-x-auto">
            <table class="rp-tbl">
                <caption class="sr-only">Sprawozdania roczne według lat</caption>
                <thead><tr><th scope="col">Rok</th>@foreach (\App\Models\AnnualReport::TYPES as $tl)<th scope="col">{{ $tl }}</th>@endforeach<th scope="col">Załączniki</th></tr></thead>
                <tbody>
                    @foreach ($reports as $report)
                        <tr id="rok-{{ $report->year }}">
                            <th scope="row">{{ $report->year }}</th>
                            @foreach (\App\Models\AnnualReport::TYPES as $typeKey => $typeLabel)
                                <td>
                                    @if ($report->fileUrlFor($typeKey))
                                        <a href="{{ $report->fileUrlFor($typeKey) }}" download class="rp-dl"><i class="fa-solid fa-file-pdf" aria-hidden="true"></i>Pobierz<span class="sr-only"> {{ mb_strtolower($typeLabel) }} {{ $report->year }} (PDF)</span></a>
                                    @else
                                        <span class="text-muted">{{ $report->messageFor($typeKey) ?? '—' }}</span>
                                    @endif
                                </td>
                            @endforeach
                            <td>
                                @php $extra = $report->getMedia('additional'); @endphp
                                @if ($extra->isEmpty())<span class="text-muted">—</span>@else
                                    <ul class="rp-extra" role="list">
                                        @foreach ($extra as $m)<li><a href="{{ $m->getUrl() }}" download class="rp-dl" style="min-height:2rem">{{ $m->name ?: $m->file_name }}<span class="sr-only"> ({{ strtoupper($m->extension ?? 'plik') }})</span></a></li>@endforeach
                                    </ul>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @include('partials.attachments-list', ['attachments' => $page->attachments])
</section>
