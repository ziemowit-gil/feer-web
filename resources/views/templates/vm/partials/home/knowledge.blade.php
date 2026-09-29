{{--
    Sekcja „Wiedza" szablonu "vm": zdjęcie po lewej, na szarym tle nagłówek
    (tytuł wybranej strony), dwukolumnowa lista jej podstron i przycisk.
    Stronę wybiera się w Ustawienia → Szablon; bez niej sekcja się nie pokazuje.
--}}
@if ($knowledgePage)
@php $vmKnowledgeImage = $siteSettings->vmKnowledgeImageUrl(); @endphp
<section class="bg-gray-100" aria-labelledby="vm-knowledge-heading">
    <div class="grid lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)]">
        <div class="h-56 bg-gray-200 sm:h-72 lg:h-auto">
            @if ($vmKnowledgeImage)
                <img src="{{ $vmKnowledgeImage }}" alt="" loading="lazy" class="h-full w-full object-cover">
            @endif
        </div>

        <div class="px-4 py-12 sm:px-10 lg:px-16">
            <h2 id="vm-knowledge-heading" class="vm-display mb-6 text-2xl text-ink sm:text-3xl">{{ $knowledgePage->title }}</h2>

            @if ($knowledgePage->publishedChildren->isNotEmpty())
                <ul class="grid list-disc gap-x-10 gap-y-2 pl-5 text-sm marker:text-ink sm:grid-cols-2" role="list">
                    @foreach ($knowledgePage->publishedChildren as $child)
                        <li>
                            <a href="{{ $child->publicUrl() }}"
                               class="vm-display block rounded py-1 text-ink hover:text-brand focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">{{ $child->title }}</a>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="mt-8 lg:text-center">
                @include('templates.vm.partials.home.more-button', ['url' => $knowledgePage->publicUrl(), 'label' => 'Dowiedz się więcej', 'context' => $knowledgePage->title])
            </div>
        </div>
    </div>
</section>
@endif
