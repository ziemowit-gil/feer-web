{{--
    „Wybierz swoją ścieżkę”: po jednej kolumnie na odbiorcę (z pola „Zakładki odbiorców”): ikona, nazwa prowadząca na stronę
    odbiorcy i do czterech pierwszych usług (podstrony tej strony) z krótkim opisem. Zastępuje zakładki i kafle.
    Dane: $servicesTabs (HomeController::servicesTabs()), adres i napis przycisku końcowego z ustawień witryny.
--}}
@php
    $servicesTabs ??= collect();
    $allUrl = $siteSettings->services_all_url ?: (\Illuminate\Support\Facades\Route::has('projects.index') ? route('projects.index') : site_route('home'));
    $allLabel = $siteSettings->services_all_label ?: 'Zobacz wszystkie usługi';
@endphp
@if ($servicesTabs->isNotEmpty())
    <section class="fis-sec" aria-labelledby="fis-aud-h">
        <div class="fis-wrap">
            <h2 id="fis-aud-h">Wybierz swoją ścieżkę</h2>
            <div class="fis-aud-grid">
                @foreach ($servicesTabs as $tab)
                    <div class="fis-aud-col fis-accent">
                        <h3>
                            <span aria-hidden="true">{{ icon_html(filled($tab['icon'] ?? null) ? $tab['icon'] : 'mi-description', '', 'font-size: 2rem') }}</span>
                            <a href="{{ $tab['url'] ?? $allUrl }}">{{ $tab['label'] }}</a>
                        </h3>
                        <ul class="fis-aud-list" role="list">
                            @foreach ($tab['services']->take(4) as $service)
                                <li>
                                    <a href="{{ $service['url'] }}">{{ $service['title'] }}</a>
                                    @if (filled($service['description']))<p>{{ \Illuminate\Support\Str::limit($service['description'], 110) }}</p>@endif
                                </li>
                            @endforeach
                        </ul>
                        @if (filled($tab['url'] ?? null))
                            <p class="fis-aud-more"><a class="fis-link" href="{{ $tab['url'] }}">Zobacz wszystko: {{ $tab['label'] }}<span aria-hidden="true"> →</span></a></p>
                        @endif
                    </div>
                @endforeach
            </div>
            <p class="fis-aud-all"><a class="fis-btn" href="{{ $allUrl }}">{{ $allLabel }}</a></p>
        </div>
    </section>
@endif
